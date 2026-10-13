<?php

declare(strict_types=1);

namespace App\Actions\Team;

use App\Enums\Role;
use App\Models\User;

final readonly class CreateTeamMember
{
    /**
     * @param  array{name: string, email: string, password: string, role: string}  $data
     */
    public function handle(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'password' => $data['password'],
            'role' => Role::from($data['role']),
        ]);
    }
}
