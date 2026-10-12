<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Tenancy\MissingTenantException;
use App\Support\Tenancy\TenantContext;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * Sanctum token that remembers its tenant, so a bearer request can establish
 * the tenant context (and the RLS setting) before the user is loaded.
 *
 * @property string $tenant_id
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /** @var list<string> */
    protected $fillable = ['name', 'token', 'abilities', 'expires_at'];

    protected static function booted(): void
    {
        static::creating(function (PersonalAccessToken $token): void {
            $tenantId = app(TenantContext::class)->id();

            if ($tenantId === null) {
                throw MissingTenantException::forModel(self::class);
            }

            $token->tenant_id = $tenantId;
        });
    }
}
