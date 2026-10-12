<?php

declare(strict_types=1);

namespace Tests;

use App\Enums\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    /** Body returned by the faked HIBP range API ("SUFFIX:COUNT" lines). */
    protected string $breachedPasswordRange = '';

    protected function setUp(): void
    {
        parent::setUp();

        // No real network calls; the breached-password check sees an empty range by default.
        Http::preventStrayRequests();
        Http::fake(['api.pwnedpasswords.com/*' => fn () => Http::response($this->breachedPasswordRange)]);
    }

    /**
     * Run a callback inside a tenant, exactly like a request would.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function inTenant(Tenant $tenant, callable $callback): mixed
    {
        return app(TenantContext::class)->run($tenant, $callback);
    }

    public function userIn(Tenant $tenant, Role $role = Role::Customer, array $attributes = []): User
    {
        return $this->inTenant($tenant, fn () => User::factory()->create(['role' => $role, ...$attributes]));
    }

    /**
     * Authenticate the next request(s) with a real Sanctum bearer token, so
     * the full tenant-resolution path (token -> tenant -> RLS) is exercised.
     */
    public function actingAsUser(User $user): static
    {
        $tenant = Tenant::findOrFail($user->tenant_id);
        $token = $this->inTenant($tenant, fn () => $user->createToken('test')->plainTextToken);

        // Guards cache the resolved user between requests in the same test.
        $this->app['auth']->forgetGuards();

        return $this->withToken($token)->withHeader('Accept', 'application/json');
    }
}
