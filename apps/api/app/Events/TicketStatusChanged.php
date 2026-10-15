<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\TicketStatus;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final class TicketStatusChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly string $tenantId,
        public readonly string $ticketId,
        public readonly TicketStatus $status,
    ) {}

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [TicketChannels::ticket($this->tenantId, $this->ticketId), TicketChannels::staff($this->tenantId)];
    }

    public function broadcastAs(): string
    {
        return 'status.changed';
    }

    /**
     * @return array<string, string>
     */
    public function broadcastWith(): array
    {
        return ['ticket_id' => $this->ticketId, 'status' => $this->status->value];
    }
}
