<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;

final class AttachmentPolicy
{
    public function __construct(private readonly TicketPolicy $tickets) {}

    public function view(User $user, Attachment $attachment): bool
    {
        return $user->tenant_id === $attachment->tenant_id
            && $this->tickets->view($user, $attachment->ticket);
    }
}
