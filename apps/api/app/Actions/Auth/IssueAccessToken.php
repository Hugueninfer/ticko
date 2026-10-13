<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;

final readonly class IssueAccessToken
{
    public function handle(User $user, string $deviceName): string
    {
        $expiresAt = now()->addMinutes(config()->integer('ticko.token_ttl_minutes'));

        return $user->createToken($deviceName, ['*'], $expiresAt)->plainTextToken;
    }
}
