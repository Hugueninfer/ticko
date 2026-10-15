<?php

declare(strict_types=1);

namespace App\Actions\Tickets;

use App\Enums\PushReason;
use App\Enums\TicketStatus;
use App\Events\TicketMessagePosted;
use App\Jobs\SendTicketPushNotification;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class PostTicketMessage
{
    public function handle(User $author, Ticket $ticket, string $body, bool $internal = false): TicketMessage
    {
        $message = DB::transaction(function () use ($author, $ticket, $body, $internal): TicketMessage {
            $message = new TicketMessage(['body' => $body, 'is_internal' => $internal]);
            $message->ticket_id = $ticket->id;
            $message->author_id = $author->id;
            $message->save();

            // A public staff reply waits on the customer; a customer reply reopens the ticket.
            if (! $internal) {
                if ($author->isStaff()) {
                    $ticket->first_response_at ??= $message->created_at;
                }

                $ticket->status = $author->isStaff() ? TicketStatus::Pending : TicketStatus::Open;
                $ticket->resolved_at = null;
                $ticket->save();
            }

            return $message;
        });

        TicketMessagePosted::dispatch($ticket->tenant_id, $ticket->id, $message->id, $internal);

        // The requester hears about public replies from the team on their phone.
        if (! $internal && $author->isStaff() && $author->id !== $ticket->requester_id) {
            SendTicketPushNotification::dispatch($ticket->tenant_id, $ticket->id, PushReason::Reply)->afterCommit();
        }

        return $message;
    }
}
