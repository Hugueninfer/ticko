<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A message was added to a ticket. Internal notes only reach the staff channel.
 */
final class TicketMessagePosted implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly string $tenantId,
        public readonly string $ticketId,
        public readonly string $messageId,
        public readonly bool $internal,
    ) {}

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $staff = TicketChannels::staff($this->tenantId);

        return $this->internal ? [$staff] : [TicketChannels::ticket($this->tenantId, $this->ticketId), $staff];
    }

    public function broadcastAs(): string
    {
        return 'message.posted';
    }

    /**
     * @return array<string, string>
     */
    public function broadcastWith(): array
    {
        return ['ticket_id' => $this->ticketId, 'message_id' => $this->messageId];
    }
}
