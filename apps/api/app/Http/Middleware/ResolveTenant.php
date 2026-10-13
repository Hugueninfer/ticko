<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\PersonalAccessToken;
use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Establishes the tenant for the request before authentication runs, from
 * (in order of precedence):
 *
 *  1. the tenant bound to a Sanctum bearer token (mobile / API clients);
 *  2. the tenant stored in the session at login (first-party SPA);
 *  3. the tenant embedded in a valid signed URL (attachment downloads);
 *  4. the X-Tenant header (slug), used to log in.
 *
 * Stronger sources win, so a client cannot switch tenant with the header.
 * If no tenant is found, the context stays empty and every tenant-scoped
 * query matches nothing.
 */
final class ResolveTenant
{
    public const string HEADER = 'X-Tenant';

    public const string SESSION_KEY = 'tenant_id';

    public function __construct(private readonly TenantContext $context) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolve($request);

        $tenant !== null ? $this->context->set($tenant) : $this->context->clear();

        return $next($request);
    }

    public function terminate(): void
    {
        $this->context->clear();
    }

    private function resolve(Request $request): ?Tenant
    {
        $bearer = $request->bearerToken();

        if ($bearer !== null) {
            $token = PersonalAccessToken::findToken($bearer);

            return $token instanceof PersonalAccessToken ? Tenant::find($token->tenant_id) : null;
        }

        if ($request->hasSession() && is_string($id = $request->session()->get(self::SESSION_KEY))) {
            return Tenant::find($id);
        }

        if ($request->query('tenant') !== null && $request->hasValidSignature()) {
            return Tenant::find((string) $request->query('tenant'));
        }

        $slug = $request->header(self::HEADER);

        return is_string($slug) && $slug !== '' ? Tenant::where('slug', $slug)->first() : null;
    }
}
