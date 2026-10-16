<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AiSuggestion;
use App\Models\User;

/** Drafts are for staff only; customers never see them. */
final class AiSuggestionPolicy
{
    public function view(User $user, AiSuggestion $suggestion): bool
    {
        return $user->isStaff() && $user->tenant_id === $suggestion->tenant_id;
    }

    /** Approve, discard or report a gap: only while the ticket accepts replies. */
    public function review(User $user, AiSuggestion $suggestion): bool
    {
        return $this->view($user, $suggestion) && ! $suggestion->ticket->status->isFinal();
    }
}
