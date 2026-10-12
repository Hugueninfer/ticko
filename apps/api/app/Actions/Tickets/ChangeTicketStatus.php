<?php

declare(strict_types=1);

namespace App\Actions\Tickets;

use App\Enums\TicketStatus;
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

        $ticket->status = $status;
        $ticket->resolved_at = $status === TicketStatus::Resolved ? now() : null;
        $ticket->closed_at = $status === TicketStatus::Closed ? now() : null;
        $ticket->save();

        return $ticket;
    }
}
