<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\IssueAccessToken;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveTenant;
use App\Http\Requests\Auth\DemoLoginRequest;
use App\Http\Requests\Auth\DemoTokenRequest;
use App\Http\Resources\UserResource;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;
use Knuckles\Scribe\Attributes\Unauthenticated;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * One-click sign-in as the seeded demo users, for local and demo environments:
 * the agent or admin in the web app, the customer in the mobile app.
 * Answers 404 unless TICKO_DEMO_LOGIN is on, and always in production, so the
 * endpoints look absent wherever they are not meant to exist.
 */
#[Group('Authentication')]
final class DemoLoginController extends Controller
{
    /**
     * Demo sign-in availability
     *
     * Lets the web app decide whether to show the demo shortcuts.
     */
    #[Unauthenticated]
    #[ScribeResponse(['data' => ['tenant' => 'acme', 'roles' => ['agent', 'admin']]])]
    public function show(): JsonResponse
    {
        return response()->json(['data' => ['tenant' => $this->demoTenant()->slug, 'roles' => ['agent', 'admin']]]);
    }

    /**
     * Sign in as a demo user (SPA session)
     *
     * Starts a session as the demo tenant's agent or admin. Same CSRF flow as
     * the regular session login.
     */
    #[Unauthenticated]
    #[ResponseFromApiResource(UserResource::class, User::class, with: ['tenant'])]
    public function store(DemoLoginRequest $request, TenantContext $tenants): UserResource
    {
        $tenant = $this->demoTenant();

        if (! $request->hasSession()) {
            throw new BadRequestHttpException('Demo sign-in is only available to the first-party web app.');
        }

        $tenants->set($tenant);
        $user = User::query()->where('role', $request->role())->where('is_active', true)->orderBy('created_at')->first()
            ?? throw new NotFoundHttpException;

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->put(ResolveTenant::SESSION_KEY, $tenant->id);

        return UserResource::make($user->load('tenant'));
    }

    /**
     * Demo customer token (mobile)
     *
     * Issues an API token for the demo tenant's customer, so the mobile app
     * can offer a one-tap demo without shipping a password.
     */
    #[Unauthenticated]
    #[ScribeResponse(['token' => '01J...|plain-text-token', 'token_type' => 'Bearer', 'expires_in' => 2592000], 201)]
    public function issueToken(DemoTokenRequest $request, TenantContext $tenants, IssueAccessToken $issue): JsonResponse
    {
        $tenants->set($this->demoTenant());

        $user = User::query()->where('role', Role::Customer)->where('is_active', true)->orderBy('created_at')->first()
            ?? throw new NotFoundHttpException;

        return response()->json([
            'token' => $issue->handle($user, $request->string('device_name')->toString()),
            'token_type' => 'Bearer',
            'expires_in' => config()->integer('ticko.token_ttl_minutes') * 60,
        ], 201);
    }

    private function demoTenant(): Tenant
    {
        if (config('ticko.demo.enabled') !== true || app()->isProduction()) {
            throw new NotFoundHttpException;
        }

        return Tenant::query()->where('slug', config()->string('ticko.demo.tenant'))->first()
            ?? throw new NotFoundHttpException;
    }
}
