<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

final class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->tenant_id !== $ticket->tenant_id) {
            return false;
        }

        return $user->isStaff() || $ticket->requester_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Staff may move a ticket to any status; requesters may only close or reopen their own.
     */
    public function changeStatus(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->isStaff() && $user->tenant_id === $ticket->tenant_id;
    }

    public function reply(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket) && ! $ticket->status->isFinal();
    }

    public function addInternalNote(User $user, Ticket $ticket): bool
    {
        return $user->isStaff() && $this->reply($user, $ticket);
    }

    /** Ask the AI for a reply draft (staff only; the draft still needs approval). */
    public function suggestReply(User $user, Ticket $ticket): bool
    {
        return $user->isStaff() && $this->reply($user, $ticket);
    }

    /** Set or correct category, priority and sentiment (staff only). */
    public function classify(User $user, Ticket $ticket): bool
    {
        return $user->isStaff() && $user->tenant_id === $ticket->tenant_id;
    }

    public function viewSuggestions(User $user, Ticket $ticket): bool
    {
        return $user->isStaff() && $this->view($user, $ticket);
    }
}
