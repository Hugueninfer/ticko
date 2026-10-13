<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Tenant;
use App\Models\Ticket;
use Illuminate\Support\Carbon;

beforeEach(function (): void {
    $this->travelTo(Carbon::parse('2026-10-12 10:00:00'));

    $this->tenant = Tenant::factory()->create();
    $this->customer = $this->userIn($this->tenant, Role::Customer);
    $this->agent = $this->userIn($this->tenant, Role::Agent);
});

it('gives new tickets deadlines from the default targets of their priority', function (): void {
    $id = $this->actingAsUser($this->customer)
        ->postJson('/api/v1/tickets', ['subject' => 'Down', 'description' => 'Everything is down', 'priority' => 'urgent'])
        ->assertCreated()
        ->assertJsonPath('data.sla.first_response_at', null)
        ->assertJsonPath('data.sla.first_response_due_at', '2026-10-12T10:15:00+00:00')
        ->assertJsonPath('data.sla.resolution_due_at', '2026-10-12T14:00:00+00:00')
        ->assertJsonPath('data.sla.is_overdue', false)
        ->json('data.id');

    expect($id)->toBeString();
});

it('uses the category targets when the category overrides the priority', function (): void {
    $category = $this->inTenant($this->tenant, fn () => Category::factory()->create([
        'sla_targets' => ['high' => ['first_response_minutes' => 30, 'resolution_minutes' => 120]],
    ]));

    $this->actingAsUser($this->customer)
        ->postJson('/api/v1/tickets', ['subject' => 'Late', 'description' => 'x', 'priority' => 'high', 'category_id' => $category->id])
        ->assertCreated()
        ->assertJsonPath('data.sla.first_response_due_at', '2026-10-12T10:30:00+00:00')
        ->assertJsonPath('data.sla.resolution_due_at', '2026-10-12T12:00:00+00:00');
});

it('records the first public staff reply only', function (): void {
    $ticket = $this->inTenant($this->tenant, fn () => Ticket::factory()->create(['requester_id' => $this->customer->id]));

    $this->actingAsUser($this->customer)->postJson("/api/v1/tickets/{$ticket->id}/messages", ['body' => 'Any news?'])->assertCreated();
    $this->actingAsUser($this->agent)->postJson("/api/v1/tickets/{$ticket->id}/messages", ['body' => 'Looking', 'is_internal' => true])->assertCreated();
    expect($this->inTenant($this->tenant, fn () => $ticket->fresh()->first_response_at))->toBeNull();

    $this->travel(5)->minutes();
    $this->actingAsUser($this->agent)->postJson("/api/v1/tickets/{$ticket->id}/messages", ['body' => 'On it'])->assertCreated();
    $this->travel(5)->minutes();
    $this->actingAsUser($this->agent)->postJson("/api/v1/tickets/{$ticket->id}/messages", ['body' => 'Fixed'])->assertCreated();

    expect($this->inTenant($this->tenant, fn () => $ticket->fresh()->first_response_at?->toDateTimeString()))->toBe('2026-10-12 10:05:00');
});

it('filters unresolved tickets with a missed deadline', function (): void {
    [$late, $answered, $resolved] = $this->inTenant($this->tenant, fn () => [
        Ticket::factory()->create(['requester_id' => $this->customer->id, 'priority' => TicketPriority::Urgent, 'subject' => 'late']),
        Ticket::factory()->create(['requester_id' => $this->customer->id, 'priority' => TicketPriority::Urgent, 'subject' => 'answered']),
        Ticket::factory()->create(['requester_id' => $this->customer->id, 'priority' => TicketPriority::Urgent, 'subject' => 'resolved']),
    ]);
    $this->inTenant($this->tenant, function () use ($answered, $resolved): void {
        $answered->forceFill(['first_response_at' => now()->addMinutes(5)])->save();
        $resolved->forceFill(['status' => TicketStatus::Resolved])->save();
    });
    $this->inTenant($this->tenant, fn () => Ticket::factory()->create(['requester_id' => $this->customer->id, 'priority' => TicketPriority::Low]));

    $this->travel(20)->minutes();

    $this->actingAsUser($this->agent)->getJson('/api/v1/tickets?overdue=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $late->id)
        ->assertJsonPath('data.0.sla.is_overdue', true);

    // Past the resolution deadline, the answered ticket is overdue too.
    $this->travel(4)->hours();
    $this->getJson('/api/v1/tickets?overdue=1&sort=resolution_due_at')->assertOk()->assertJsonCount(2, 'data');
});

it('lets admins override SLA targets per category and reset them', function (): void {
    $admin = $this->userIn($this->tenant, Role::Admin);
    $category = $this->inTenant($this->tenant, fn () => Category::factory()->create());

    $this->actingAsUser($admin)
        ->patchJson("/api/v1/categories/{$category->id}", ['sla_targets' => ['urgent' => ['first_response_minutes' => 10, 'resolution_minutes' => 60]]])
        ->assertOk()
        ->assertJsonPath('data.sla_targets.urgent', ['first_response_minutes' => 10, 'resolution_minutes' => 60, 'is_custom' => true])
        ->assertJsonPath('data.sla_targets.low', ['first_response_minutes' => 480, 'resolution_minutes' => 4320, 'is_custom' => false]);

    $this->patchJson("/api/v1/categories/{$category->id}", ['sla_targets' => null])
        ->assertOk()
        ->assertJsonPath('data.sla_targets.urgent.is_custom', false);
});

it('rejects invalid SLA targets', function (array $targets, string $error): void {
    $admin = $this->userIn($this->tenant, Role::Admin);

    $this->actingAsUser($admin)
        ->postJson('/api/v1/categories', ['name' => 'Billing', 'sla_targets' => $targets])
        ->assertUnprocessable()
        ->assertJsonValidationErrors($error);
})->with([
    'unknown priority' => [['critical' => ['first_response_minutes' => 1, 'resolution_minutes' => 2]], 'sla_targets'],
    'missing value' => [['high' => ['first_response_minutes' => 10]], 'sla_targets.high.resolution_minutes'],
    'resolution before first response' => [['high' => ['first_response_minutes' => 60, 'resolution_minutes' => 30]], 'sla_targets.high.resolution_minutes'],
    'zero' => [['low' => ['first_response_minutes' => 0, 'resolution_minutes' => 10]], 'sla_targets.low.first_response_minutes'],
]);
