<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\TicketMessage;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    $this->customer = $this->userIn($this->tenant, Role::Customer);
    $this->otherCustomer = $this->userIn($this->tenant, Role::Customer);
    $this->agent = $this->userIn($this->tenant, Role::Agent);
    $this->admin = $this->userIn($this->tenant, Role::Admin);
    $this->category = $this->inTenant($this->tenant, fn () => Category::factory()->create());
});

function ticketFor(object $test, $requester, array $attributes = []): Ticket
{
    return $test->inTenant($test->tenant, fn () => Ticket::factory()->create(['requester_id' => $requester->id, ...$attributes]));
}

it('opens a ticket as the authenticated requester', function (): void {
    $this->actingAsUser($this->customer)->postJson('/api/v1/tickets', [
        'subject' => 'VPN is down',
        'description' => 'Cannot connect since 9am.',
        'priority' => 'high',
        'category_id' => $this->category->id,
        'status' => 'closed',          // not accepted from input
        'requester_id' => $this->agent->id, // not accepted from input
    ])
        ->assertCreated()
        ->assertJsonPath('data.subject', 'VPN is down')
        ->assertJsonPath('data.status', 'open')
        ->assertJsonPath('data.priority', 'high')
        ->assertJsonPath('data.category.id', $this->category->id)
        ->assertJsonPath('data.requester.id', $this->customer->id);

    $this->inTenant($this->tenant, fn () => expect(Ticket::first()->tenant_id)->toBe($this->tenant->id));
});

it('validates ticket input', function (): void {
    $this->actingAsUser($this->customer)
        ->postJson('/api/v1/tickets', ['subject' => 'x', 'priority' => 'whenever', 'category_id' => 'not-a-ulid'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['subject', 'description', 'priority', 'category_id']);
});

it('rejects a category from another tenant', function (): void {
    $foreign = Tenant::factory()->create();
    $foreignCategory = $this->inTenant($foreign, fn () => Category::factory()->create());

    $this->actingAsUser($this->customer)->postJson('/api/v1/tickets', [
        'subject' => 'Hello there', 'description' => 'Body', 'category_id' => $foreignCategory->id,
    ])->assertUnprocessable()->assertJsonValidationErrors('category_id');
});

it('shows customers only their own tickets', function (): void {
    $mine = ticketFor($this, $this->customer);
    ticketFor($this, $this->otherCustomer);

    $this->actingAsUser($this->customer)->getJson('/api/v1/tickets')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $mine->id)
        ->assertJsonStructure(['data', 'links', 'meta' => ['current_page', 'per_page', 'total']]);
});

it('ignores the requester filter for customers', function (): void {
    ticketFor($this, $this->otherCustomer);

    $this->actingAsUser($this->customer)
        ->getJson('/api/v1/tickets?requester_id='.$this->otherCustomer->id)
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('shows staff every ticket of the tenant', function (Role $role): void {
    ticketFor($this, $this->customer);
    ticketFor($this, $this->otherCustomer);
    $user = $role === Role::Agent ? $this->agent : $this->admin;

    $this->actingAsUser($user)->getJson('/api/v1/tickets')->assertOk()->assertJsonCount(2, 'data');
})->with([Role::Agent, Role::Admin]);

it('filters, searches, sorts and paginates tickets', function (): void {
    ticketFor($this, $this->customer, ['subject' => 'Printer jammed', 'status' => TicketStatus::Open, 'priority' => TicketPriority::Low]);
    ticketFor($this, $this->customer, ['subject' => 'Laptop broken', 'status' => TicketStatus::Pending, 'priority' => TicketPriority::Urgent, 'assignee_id' => $this->agent->id, 'category_id' => $this->category->id]);
    ticketFor($this, $this->otherCustomer, ['subject' => 'PRINTER out of toner', 'status' => TicketStatus::Open, 'priority' => TicketPriority::High]);

    $this->actingAsUser($this->agent);

    $this->getJson('/api/v1/tickets?status=open')->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/tickets?priority=urgent')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/tickets?assignee_id='.$this->agent->id)->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/tickets?unassigned=1')->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/tickets?category_id='.$this->category->id)->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/tickets?requester_id='.$this->otherCustomer->id)->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/tickets?q=printer')->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/tickets?q=%25')->assertJsonCount(0, 'data');
    $this->getJson('/api/v1/tickets?sort=subject')->assertUnprocessable();
    $this->getJson('/api/v1/tickets?per_page=500')->assertUnprocessable();

    $this->getJson('/api/v1/tickets?per_page=2&page=2')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.total', 3);

    $priorities = $this->getJson('/api/v1/tickets?sort=-priority')->json('data.*.priority');
    expect($priorities)->toBe(['urgent', 'high', 'low']);
});

it('lets the requester see their ticket but not others', function (): void {
    $mine = ticketFor($this, $this->customer);
    $theirs = ticketFor($this, $this->otherCustomer);

    $this->actingAsUser($this->customer);
    $this->getJson("/api/v1/tickets/{$mine->id}")->assertOk()->assertJsonPath('data.id', $mine->id);
    $this->getJson("/api/v1/tickets/{$theirs->id}")->assertForbidden();
});

it('lets staff move a ticket through every status', function (): void {
    $ticket = ticketFor($this, $this->customer);
    $this->actingAsUser($this->agent);

    $this->patchJson("/api/v1/tickets/{$ticket->id}/status", ['status' => 'resolved'])
        ->assertOk()
        ->assertJsonPath('data.status', 'resolved')
        ->assertJsonPath('data.closed_at', null);

    expect($this->getJson("/api/v1/tickets/{$ticket->id}")->json('data.resolved_at'))->not->toBeNull();

    $this->patchJson("/api/v1/tickets/{$ticket->id}/status", ['status' => 'closed'])
        ->assertOk()
        ->assertJsonPath('data.status', 'closed')
        ->assertJsonPath('data.resolved_at', null);

    $this->patchJson("/api/v1/tickets/{$ticket->id}/status", ['status' => 'bogus'])->assertUnprocessable();
});

it('lets requesters only close or reopen their own tickets', function (string $status, int $expected): void {
    $ticket = ticketFor($this, $this->customer);

    $this->actingAsUser($this->customer)
        ->patchJson("/api/v1/tickets/{$ticket->id}/status", ['status' => $status])
        ->assertStatus($expected);
})->with([
    'close' => ['closed', 200],
    'reopen' => ['open', 200],
    'resolve' => ['resolved', 403],
    'pending' => ['pending', 403],
]);

it('lets staff assign and unassign tickets', function (): void {
    $ticket = ticketFor($this, $this->customer);
    $this->actingAsUser($this->admin);

    $this->patchJson("/api/v1/tickets/{$ticket->id}/assignee", ['assignee_id' => $this->agent->id])
        ->assertOk()
        ->assertJsonPath('data.assignee.id', $this->agent->id);

    $this->patchJson("/api/v1/tickets/{$ticket->id}/assignee", ['assignee_id' => null])
        ->assertOk()
        ->assertJsonPath('data.assignee', null);
});

it('only assigns to active staff of the same tenant', function (): void {
    $ticket = ticketFor($this, $this->customer);
    $inactiveAgent = $this->userIn($this->tenant, Role::Agent, ['is_active' => false]);
    $foreignAgent = $this->userIn(Tenant::factory()->create(), Role::Agent);
    $this->actingAsUser($this->agent);

    foreach ([$this->customer->id, $inactiveAgent->id, $foreignAgent->id] as $assignee) {
        $this->patchJson("/api/v1/tickets/{$ticket->id}/assignee", ['assignee_id' => $assignee])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('assignee_id');
    }

    $this->patchJson("/api/v1/tickets/{$ticket->id}/assignee", [])->assertJsonValidationErrors('assignee_id');
});

it('forbids customers from assigning tickets', function (): void {
    $ticket = ticketFor($this, $this->customer);

    $this->actingAsUser($this->customer)
        ->patchJson("/api/v1/tickets/{$ticket->id}/assignee", ['assignee_id' => $this->agent->id])
        ->assertForbidden()
        ->assertHeader('Content-Type', 'application/problem+json');
});

it('shows people\'s e-mails to staff only', function (): void {
    $customer = $this->userIn($this->tenant, Role::Customer);
    $agent = $this->userIn($this->tenant, Role::Agent);
    $ticket = $this->inTenant($this->tenant, fn () => Ticket::factory()->create(['requester_id' => $customer->id, 'assignee_id' => $agent->id]));

    $this->actingAsUser($agent)->getJson("/api/v1/tickets/{$ticket->id}")
        ->assertOk()
        ->assertJsonPath('data.requester.email', $customer->email);

    $this->actingAsUser($customer)->getJson("/api/v1/tickets/{$ticket->id}")
        ->assertOk()
        ->assertJsonPath('data.assignee.email', null)
        ->assertJsonPath('data.requester.email', null);
});

it('lists each ticket with an excerpt of its latest public message', function (): void {
    $ticket = ticketFor($this, $this->customer);
    $quiet = ticketFor($this, $this->customer);
    $this->inTenant($this->tenant, function () use ($ticket): void {
        TicketMessage::factory()->create(['ticket_id' => $ticket->id, 'author_id' => $this->customer->id, 'body' => 'First', 'created_at' => now()->subHour()]);
        TicketMessage::factory()->create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'body' => "We are   on it,\nhang tight. ".str_repeat('x', 200)]);
        TicketMessage::factory()->internal()->create(['ticket_id' => $ticket->id, 'author_id' => $this->agent->id, 'body' => 'Internal gossip', 'created_at' => now()->addMinute()]);
    });

    $response = $this->actingAsUser($this->customer)->getJson('/api/v1/tickets')->assertOk();
    $rows = collect($response->json('data'))->keyBy('id');

    expect($rows[$quiet->id]['last_message'])->toBeNull()
        ->and($rows[$ticket->id]['last_message']['author_name'])->toBe($this->agent->name)
        ->and($rows[$ticket->id]['last_message']['author_role'])->toBe('agent')
        ->and($rows[$ticket->id]['last_message']['excerpt'])->toStartWith('We are on it, hang tight.')
        ->and(mb_strlen($rows[$ticket->id]['last_message']['excerpt']))->toBeLessThanOrEqual(143)
        ->and($response->content())->not->toContain('Internal gossip');
});

it('filters tickets by state', function (string $state, array $expected): void {
    foreach (TicketStatus::cases() as $status) {
        ticketFor($this, $this->customer, ['status' => $status]);
    }

    $statuses = collect($this->actingAsUser($this->customer)->getJson("/api/v1/tickets?state={$state}")->assertOk()->json('data'))
        ->pluck('status')->sort()->values()->all();

    expect($statuses)->toBe($expected);
})->with([
    'active' => ['active', ['open', 'pending']],
    'done' => ['done', ['closed', 'resolved']],
]);

it('rejects unknown states', function (): void {
    $this->actingAsUser($this->customer)->getJson('/api/v1/tickets?state=archived')->assertUnprocessable()->assertJsonValidationErrors('state');
});

it('moves a ticket to the top of the recently updated list when someone replies', function (): void {
    $old = ticketFor($this, $this->customer, ['updated_at' => now()->subDay()]);
    ticketFor($this, $this->customer, ['updated_at' => now()->subHour()]);

    $this->actingAsUser($this->agent)->postJson("/api/v1/tickets/{$old->id}/messages", ['body' => 'Update'])->assertCreated();

    $this->actingAsUser($this->customer)->getJson('/api/v1/tickets?sort=-updated_at')->assertJsonPath('data.0.id', $old->id);
});
