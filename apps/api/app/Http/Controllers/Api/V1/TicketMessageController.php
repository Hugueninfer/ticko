<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Tickets\PostTicketMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tickets\StoreTicketMessageRequest;
use App\Http\Resources\TicketMessageResource;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;

#[Group('Ticket messages', 'Conversation on a ticket. Internal notes are only visible to staff.')]
final class TicketMessageController extends Controller
{
    /**
     * List messages
     */
    #[ResponseFromApiResource(TicketMessageResource::class, TicketMessage::class, collection: true, paginate: 50, with: ['author'])]
    public function index(Request $request, Ticket $ticket): AnonymousResourceCollection
    {
        Gate::authorize('view', $ticket);

        $messages = $ticket->messages()
            ->with('author')
            ->when(! $this->currentUser($request)->isStaff(), fn ($q) => $q->where('is_internal', false))
            ->oldest()
            ->orderBy('id')
            ->paginate(50);

        return TicketMessageResource::collection($messages);
    }

    /**
     * Post a message
     */
    #[ResponseFromApiResource(TicketMessageResource::class, TicketMessage::class, 201, with: ['author'])]
    public function store(StoreTicketMessageRequest $request, Ticket $ticket, PostTicketMessage $post): TicketMessageResource
    {
        $internal = $request->boolean('is_internal');
        Gate::authorize($internal ? 'addInternalNote' : 'reply', $ticket);

        $message = $post->handle($this->currentUser($request), $ticket, $request->string('body')->toString(), $internal);

        return TicketMessageResource::make($message->load('author'));
    }
}
