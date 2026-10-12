<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Enums\TicketStatus;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\TicketMessage;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    $this->customer = $this->userIn($this->tenant, Role::Customer);
    $this->agent = $this->userIn($this->tenant, Role::Agent);
    $this->ticket = $this->inTenant($this->tenant, fn () => Ticket::factory()->create(['requester_id' => $this->customer->id]));
});

it('lets the requester and staff converse on a ticket', function (): void {
    $this->actingAsUser($this->agent)
        ->postJson("/api/v1/tickets/{$this->ticket->id}/messages", ['body' => 'Have you tried turning it off and on?'])
        ->assertCreated()
        ->assertJsonPath('data.author.id', $this->agent->id)
        ->assertJsonPath('data.is_internal', false);

    $this->inTenant($this->tenant, fn () => expect($this->ticket->fresh()->status)->toBe(TicketStatus::Pending));

    $this->actingAsUser($this->customer)
        ->postJson("/api/v1/tickets/{$this->ticket->id}/messages", ['body' => 'Yes, still broken.'])
        ->assertCreated();

    $this->inTenant($this->tenant, fn () => expect($this->ticket->fresh()->status)->toBe(TicketStatus::Open));

    $this->getJson("/api/v1/tickets/{$this->ticket->id}/messages")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.author.id', $this->agent->id);
});

it('hides internal notes from customers', function (): void {
    $this->actingAsUser($this->agent)
        ->postJson("/api/v1/tickets/{$this->ticket->id}/messages", ['body' => 'Customer seems frustrated', 'is_internal' => true])
        ->assertCreated()
        ->assertJsonPath('data.is_internal', true);

    // Internal notes do not change the ticket status.
    $this->inTenant($this->tenant, fn () => expect($this->ticket->fresh()->status)->toBe(TicketStatus::Open));

    $this->getJson("/api/v1/tickets/{$this->ticket->id}/messages")->assertJsonCount(1, 'data');
    $this->actingAsUser($this->customer)->getJson("/api/v1/tickets/{$this->ticket->id}/messages")->assertJsonCount(0, 'data');
});

it('forbids customers from writing internal notes', function (): void {
    $this->actingAsUser($this->customer)
        ->postJson("/api/v1/tickets/{$this->ticket->id}/messages", ['body' => 'sneaky', 'is_internal' => true])
        ->assertForbidden();

    $this->inTenant($this->tenant, fn () => expect(TicketMessage::count())->toBe(0));
});

it('forbids replies on closed tickets and on tickets of other customers', function (): void {
    $stranger = $this->userIn($this->tenant, Role::Customer);

    $this->actingAsUser($stranger)
        ->postJson("/api/v1/tickets/{$this->ticket->id}/messages", ['body' => 'hi'])
        ->assertForbidden();
    $this->getJson("/api/v1/tickets/{$this->ticket->id}/messages")->assertForbidden();

    $this->inTenant($this->tenant, fn () => $this->ticket->forceFill(['status' => TicketStatus::Closed])->save());

    $this->actingAsUser($this->customer)
        ->postJson("/api/v1/tickets/{$this->ticket->id}/messages", ['body' => 'hello?'])
        ->assertForbidden();
});

it('validates the message body', function (): void {
    $this->actingAsUser($this->customer)
        ->postJson("/api/v1/tickets/{$this->ticket->id}/messages", ['body' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('body');
});
