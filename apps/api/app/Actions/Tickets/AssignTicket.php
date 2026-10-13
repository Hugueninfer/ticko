<?php

declare(strict_types=1);

namespace App\Actions\Tickets;

use App\Models\Ticket;
use App\Models\User;

final readonly class AssignTicket
{
    public function handle(Ticket $ticket, ?User $assignee): Ticket
    {
        $ticket->assignee_id = $assignee?->id;
        $ticket->save();

        return $ticket;
    }
}
