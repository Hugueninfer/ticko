<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/** An AI reply suggestion changed state (staff only: customers never see drafts). */
final class SuggestionUpdated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly string $tenantId,
        public readonly string $ticketId,
        public readonly string $suggestionId,
    ) {}

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [TicketChannels::staff($this->tenantId)];
    }

    public function broadcastAs(): string
    {
        return 'suggestion.updated';
    }

    /**
     * @return array<string, string>
     */
    public function broadcastWith(): array
    {
        return ['ticket_id' => $this->ticketId, 'suggestion_id' => $this->suggestionId];
    }
}
