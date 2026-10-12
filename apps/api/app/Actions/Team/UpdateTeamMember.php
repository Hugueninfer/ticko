<?php

declare(strict_types=1);

namespace App\Actions\Team;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class UpdateTeamMember
{
    /**
     * @param  array{name?: string, role?: string, is_active?: bool}  $data
     */
    public function handle(User $member, array $data): User
    {
        return DB::transaction(function () use ($member, $data): User {
            $member->fill($data)->save();

            // Deactivation or a role change revokes existing API tokens.
            if ($member->wasChanged(['is_active', 'role'])) {
                $member->tokens()->delete();
            }

            return $member;
        });
    }
}
