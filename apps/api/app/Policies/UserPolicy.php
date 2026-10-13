<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Governs the team (user management) endpoints.
 */
final class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, User $member): bool
    {
        return $user->isStaff() && $user->tenant_id === $member->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Admins manage members of their tenant, but not themselves, so a tenant
     * cannot accidentally lose its last admin through self-demotion.
     */
    public function update(User $user, User $member): bool
    {
        return $user->isAdmin()
            && $user->tenant_id === $member->tenant_id
            && $user->id !== $member->id;
    }
}
