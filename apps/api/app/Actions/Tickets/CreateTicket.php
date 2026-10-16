<?php

declare(strict_types=1);

namespace App\Actions\Tickets;

use App\Enums\FieldSource;
use App\Enums\TicketStatus;
use App\Events\TicketCreated;
use App\Jobs\ClassifyTicket;
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
        // What the requester chose explicitly is never changed by the classifier.
        $ticket->category_source = isset($data['category_id']) ? FieldSource::Requester : null;
        $ticket->priority_source = isset($data['priority']) ? FieldSource::Requester : null;
        $ticket->save();

        TicketCreated::dispatch($ticket->tenant_id, $ticket->id);

        if (config()->string('ticko.classifier.url') !== '') {
            ClassifyTicket::dispatch($ticket->tenant_id, $ticket->id)->afterCommit();
        }

        return $ticket;
    }
}
