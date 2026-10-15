<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Tickets\AssignTicket;
use App\Actions\Tickets\ChangeTicketStatus;
use App\Actions\Tickets\CreateTicket;
use App\Actions\Tickets\ListTickets;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tickets\AssignTicketRequest;
use App\Http\Requests\Tickets\ChangeTicketStatusRequest;
use App\Http\Requests\Tickets\IndexTicketsRequest;
use App\Http\Requests\Tickets\StoreTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;

#[Group('Tickets', 'Customers see and manage only their own tickets; agents and admins see every ticket of the tenant.')]
final class TicketController extends Controller
{
    /**
     * List tickets
     */
    #[ResponseFromApiResource(TicketResource::class, Ticket::class, collection: true, paginate: 25, with: ['requester', 'assignee', 'category', 'lastPublicMessage.author'])]
    public function index(IndexTicketsRequest $request, ListTickets $list): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Ticket::class);

        return TicketResource::collection($list->handle($this->currentUser($request), $request->filters()));
    }

    /**
     * Open a ticket
     */
    #[ResponseFromApiResource(TicketResource::class, Ticket::class, 201, with: ['requester', 'assignee', 'category'])]
    public function store(StoreTicketRequest $request, CreateTicket $create): TicketResource
    {
        Gate::authorize('create', Ticket::class);

        /** @var array{subject: string, description: string, priority?: string, category_id?: string|null} $data */
        $data = $request->validated();
        $ticket = $create->handle($this->currentUser($request), $data);

        return TicketResource::make($ticket->load(['requester', 'assignee', 'category']));
    }

    /**
     * Show a ticket
     */
    #[ResponseFromApiResource(TicketResource::class, Ticket::class, with: ['requester', 'assignee', 'category'])]
    public function show(Ticket $ticket): TicketResource
    {
        Gate::authorize('view', $ticket);

        return TicketResource::make($ticket->load(['requester', 'assignee', 'category']));
    }

    /**
     * Change ticket status
     */
    #[ResponseFromApiResource(TicketResource::class, Ticket::class, with: ['requester', 'assignee', 'category'])]
    public function updateStatus(ChangeTicketStatusRequest $request, Ticket $ticket, ChangeTicketStatus $change): TicketResource
    {
        Gate::authorize('changeStatus', $ticket);

        $ticket = $change->handle($this->currentUser($request), $ticket, $request->status());

        return TicketResource::make($ticket->load(['requester', 'assignee', 'category']));
    }

    /**
     * Assign a ticket
     *
     * Staff only. Pass `null` to unassign.
     */
    #[ResponseFromApiResource(TicketResource::class, Ticket::class, with: ['requester', 'assignee', 'category'])]
    public function assign(AssignTicketRequest $request, Ticket $ticket, AssignTicket $assign): TicketResource
    {
        Gate::authorize('assign', $ticket);

        $ticket = $assign->handle($ticket, $request->assignee());

        return TicketResource::make($ticket->load(['requester', 'assignee', 'category']));
    }
}
