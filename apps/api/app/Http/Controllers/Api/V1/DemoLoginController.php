<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveTenant;
use App\Http\Requests\Auth\DemoLoginRequest;
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
 * One-click sign-in as the seeded demo users, for local and demo environments.
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

    private function demoTenant(): Tenant
    {
        if (config('ticko.demo.enabled') !== true || app()->isProduction()) {
            throw new NotFoundHttpException;
        }

        return Tenant::query()->where('slug', config()->string('ticko.demo.tenant'))->first()
            ?? throw new NotFoundHttpException;
    }
}
