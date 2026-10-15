<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Enums\TicketStatus;
use App\Events\TicketCreated;
use App\Events\TicketMessagePosted;
use App\Events\TicketStatusChanged;
use App\Models\Tenant;
use App\Models\Ticket;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    $this->customer = $this->userIn($this->tenant, Role::Customer);
    $this->agent = $this->userIn($this->tenant, Role::Agent);
    $this->ticket = $this->inTenant($this->tenant, fn () => Ticket::factory()->create(['requester_id' => $this->customer->id]));
});

/** @param list<PrivateChannel> $channels */
function channelNames(array $channels): array
{
    return array_map(fn (PrivateChannel $channel): string => $channel->name, $channels);
}

describe('events', function (): void {
    beforeEach(fn () => Event::fake([TicketCreated::class, TicketMessagePosted::class, TicketStatusChanged::class]));

    it('announces public messages to the ticket and the staff, with ids only', function (): void {
        $id = $this->actingAsUser($this->agent)
            ->postJson("/api/v1/tickets/{$this->ticket->id}/messages", ['body' => 'Secret details'])
            ->json('data.id');

        Event::assertDispatched(TicketMessagePosted::class, function (TicketMessagePosted $event) use ($id): bool {
            expect(channelNames($event->broadcastOn()))->toBe([
                "private-tenant.{$this->tenant->id}.tickets.{$this->ticket->id}",
                "private-tenant.{$this->tenant->id}.staff",
            ])
                ->and($event->broadcastAs())->toBe('message.posted')
                ->and($event->broadcastWith())->toBe(['ticket_id' => $this->ticket->id, 'message_id' => $id]);

            return true;
        });
    });

    it('announces internal notes to the staff only', function (): void {
        $this->actingAsUser($this->agent)
            ->postJson("/api/v1/tickets/{$this->ticket->id}/messages", ['body' => 'Note', 'is_internal' => true])
            ->assertCreated();

        Event::assertDispatched(TicketMessagePosted::class, fn (TicketMessagePosted $event): bool => channelNames($event->broadcastOn()) === ["private-tenant.{$this->tenant->id}.staff"]);
    });

    it('announces status changes and new tickets', function (): void {
        $this->actingAsUser($this->agent)->patchJson("/api/v1/tickets/{$this->ticket->id}/status", ['status' => 'resolved'])->assertOk();
        $created = $this->actingAsUser($this->customer)->postJson('/api/v1/tickets', ['subject' => 'Help', 'description' => 'Broken'])->json('data.id');

        Event::assertDispatched(TicketStatusChanged::class, fn (TicketStatusChanged $event): bool => $event->broadcastAs() === 'status.changed'
            && $event->broadcastWith() === ['ticket_id' => $this->ticket->id, 'status' => TicketStatus::Resolved->value]
            && count($event->broadcastOn()) === 2);
        Event::assertDispatched(TicketCreated::class, fn (TicketCreated $event): bool => $event->broadcastAs() === 'ticket.created'
            && $event->broadcastWith() === ['ticket_id' => $created]
            && channelNames($event->broadcastOn()) === ["private-tenant.{$this->tenant->id}.staff"]);
    });
});

describe('channel authorization', function (): void {
    beforeEach(function (): void {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
        ]);
        // Channels are registered on the broadcaster in use; register them on Reverb.
        require base_path('routes/channels.php');
    });

    function authorizeChannel(string $channel): Illuminate\Testing\TestResponse
    {
        return test()->postJson('/api/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => "private-{$channel}"]);
    }

    it('lets the requester and the staff follow a ticket', function (): void {
        $channel = "tenant.{$this->tenant->id}.tickets.{$this->ticket->id}";

        $this->actingAsUser($this->customer);
        authorizeChannel($channel)->assertOk()->assertJsonStructure(['auth']);
        expect(authorizeChannel($channel)->json('auth'))->toStartWith('test-key:');

        $this->actingAsUser($this->agent);
        authorizeChannel($channel)->assertOk();
        authorizeChannel("tenant.{$this->tenant->id}.staff")->assertOk();
    });

    it('refuses other customers and the staff channel to customers', function (): void {
        $stranger = $this->userIn($this->tenant, Role::Customer);

        $this->actingAsUser($stranger);
        authorizeChannel("tenant.{$this->tenant->id}.tickets.{$this->ticket->id}")->assertForbidden();
        authorizeChannel("tenant.{$this->tenant->id}.staff")->assertForbidden();
    });

    it('refuses users of another tenant, whatever tenant the channel names', function (): void {
        $globex = Tenant::factory()->create();
        $foreignAdmin = $this->userIn($globex, Role::Admin);

        $this->actingAsUser($foreignAdmin);
        authorizeChannel("tenant.{$this->tenant->id}.tickets.{$this->ticket->id}")->assertForbidden();
        authorizeChannel("tenant.{$this->tenant->id}.staff")->assertForbidden();
        // Own tenant id with a foreign ticket id: the ticket is invisible (scope + RLS).
        authorizeChannel("tenant.{$globex->id}.tickets.{$this->ticket->id}")->assertForbidden();
    });

    it('requires authentication', function (): void {
        authorizeChannel("tenant.{$this->tenant->id}.staff")->assertUnauthorized();
    });
});

it('tells clients whether realtime is available and its public key', function (): void {
    $this->actingAsUser($this->customer)->getJson('/api/v1/realtime')
        ->assertOk()
        ->assertExactJson(['data' => ['enabled' => false, 'key' => null]]);

    config(['broadcasting.default' => 'reverb', 'broadcasting.connections.reverb.key' => 'public-key']);

    $this->getJson('/api/v1/realtime')->assertExactJson(['data' => ['enabled' => true, 'key' => 'public-key']]);
    // The secret is never exposed.
    expect($this->getJson('/api/v1/realtime')->content())->not->toContain((string) config('broadcasting.connections.reverb.secret') ?: 'unused');
});
