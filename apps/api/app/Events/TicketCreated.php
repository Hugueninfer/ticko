<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final class TicketCreated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly string $tenantId,
        public readonly string $ticketId,
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
        return 'ticket.created';
    }

    /**
     * @return array<string, string>
     */
    public function broadcastWith(): array
    {
        return ['ticket_id' => $this->ticketId];
    }
}
