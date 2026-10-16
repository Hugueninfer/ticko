<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use App\Support\Telemetry\Metrics;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Verifies credentials inside the current tenant without revealing whether
 * the tenant, the account, or only the password was wrong: every failure
 * produces the same error and costs one password hash.
 */
final readonly class AuthenticateUser
{
    /**
     * Argon2id hash (production cost parameters) of a random, discarded value.
     * Checked when the account does not exist, so that path costs the same.
     */
    private const string DUMMY_HASH = '$argon2id$v=19$m=65536,t=4,p=1$dkx4bkw5SGJVLmh2SWViMw$C70+JUxKAYex37wgWM40w0lUUmeDPQHIQROScdeiAdY';

    public function __construct(private TenantContext $tenants, private Metrics $metrics) {}

    public function handle(string $email, string $password): User
    {
        $user = $this->tenants->check()
            ? User::query()->where('email', mb_strtolower($email))->first()
            : null;

        $valid = $user !== null
            ? Hash::check($password, $user->password)
            : Hash::check($password, self::DUMMY_HASH);

        if ($user === null || ! $valid || ! $user->is_active) {
            $this->metrics->loginFailure();

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if (Hash::needsRehash($user->password)) {
            $user->forceFill(['password' => $password])->save();
        }

        return $user;
    }
}
