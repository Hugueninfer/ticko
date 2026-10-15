<?php

declare(strict_types=1);

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;

/*
| Private channel authorization (POST /api/broadcasting/auth). The tenant
| context is already set from the caller's token or session, so lookups are
| scoped (and RLS-protected); the tenant in the channel name must also match.
*/

Broadcast::channel('tenant.{tenantId}.tickets.{ticketId}', function (User $user, string $tenantId, string $ticketId): bool {
    if ($user->tenant_id !== $tenantId) {
        return false;
    }

    $ticket = Ticket::query()->find($ticketId);

    return $ticket !== null && Gate::forUser($user)->allows('view', $ticket);
});

Broadcast::channel('tenant.{tenantId}.staff', fn (User $user, string $tenantId): bool => $user->tenant_id === $tenantId && $user->isStaff());
