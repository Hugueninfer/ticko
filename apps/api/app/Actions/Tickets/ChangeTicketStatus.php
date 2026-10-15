<?php

declare(strict_types=1);

namespace App\Actions\Tickets;

use App\Enums\PushReason;
use App\Enums\TicketStatus;
use App\Events\TicketStatusChanged;
use App\Jobs\SendTicketPushNotification;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final readonly class ChangeTicketStatus
{
    /**
     * Statuses a requester (customer) may set on their own ticket.
     *
     * @var list<TicketStatus>
     */
    private const array REQUESTER_STATUSES = [TicketStatus::Open, TicketStatus::Closed];

    public function handle(User $actor, Ticket $ticket, TicketStatus $status): Ticket
    {
        if (! $actor->isStaff() && ! in_array($status, self::REQUESTER_STATUSES, true)) {
            throw new AuthorizationException;
        }

        $changed = $ticket->status !== $status;

        $ticket->status = $status;
        $ticket->resolved_at = $status === TicketStatus::Resolved ? now() : null;
        $ticket->closed_at = $status === TicketStatus::Closed ? now() : null;
        $ticket->save();

        if ($changed) {
            TicketStatusChanged::dispatch($ticket->tenant_id, $ticket->id, $status);

            if ($actor->id !== $ticket->requester_id) {
                SendTicketPushNotification::dispatch($ticket->tenant_id, $ticket->id, PushReason::StatusChange)->afterCommit();
            }
        }

        return $ticket;
    }
}
