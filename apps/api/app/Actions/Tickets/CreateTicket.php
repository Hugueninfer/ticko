<?php

declare(strict_types=1);

namespace App\Actions\Tickets;

use App\Enums\TicketStatus;
use App\Events\TicketCreated;
use App\Models\Ticket;
use App\Models\User;

final readonly class CreateTicket
{
    /**
     * @param  array{subject: string, description: string, priority?: string, category_id?: string|null}  $data
     */
    public function handle(User $requester, array $data): Ticket
    {
        $ticket = new Ticket($data);
        $ticket->requester_id = $requester->id;
        $ticket->status = TicketStatus::Open;
        $ticket->save();

        TicketCreated::dispatch($ticket->tenant_id, $ticket->id);

        return $ticket;
    }
}
