<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\AuthenticateUser;
use App\Actions\Auth\IssueAccessToken;
use App\Http\Attributes\SelfService;
use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveTenant;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\TokenRequest;
use App\Http\Requests\Auth\UpdateNotificationPreferencesRequest;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Header;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;
use Knuckles\Scribe\Attributes\Unauthenticated;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[Group('Authentication', 'Session (SPA) and token (mobile) authentication. Send the tenant slug in the X-Tenant header to log in.')]
final class AuthController extends Controller
{
    /**
     * Log in (SPA session)
     *
     * Starts a cookie-based session for the first-party web app. Call
     * `GET /sanctum/csrf-cookie` first and send the `X-XSRF-TOKEN` header.
     */
    #[Unauthenticated]
    #[Header(ResolveTenant::HEADER, 'acme')]
    #[ResponseFromApiResource(UserResource::class, User::class, with: ['tenant'])]
    public function login(LoginRequest $request, AuthenticateUser $authenticate): UserResource
    {
        if (! $request->hasSession()) {
            throw new BadRequestHttpException('Session login is only available to the first-party web app. Use POST /api/v1/auth/tokens.');
        }

        $user = $authenticate->handle($request->string('email')->toString(), $request->string('password')->toString());

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->put(ResolveTenant::SESSION_KEY, $user->tenant_id);

        return UserResource::make($user->load('tenant'));
    }

    /**
     * Log out (SPA session)
     */
    #[SelfService]
    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->noContent();
    }

    /**
     * Issue an API token (mobile)
     *
     * Returns a bearer token bound to the user's tenant. Tokens expire and
     * are revoked when the user is deactivated or changes role.
     */
    #[Unauthenticated]
    #[Header(ResolveTenant::HEADER, 'acme')]
    #[ScribeResponse(['token' => '01J...|plain-text-token', 'token_type' => 'Bearer', 'expires_in' => 2592000], 201)]
    public function issueToken(TokenRequest $request, AuthenticateUser $authenticate, IssueAccessToken $issue): JsonResponse
    {
        $user = $authenticate->handle($request->string('email')->toString(), $request->string('password')->toString());

        return response()->json([
            'token' => $issue->handle($user, $request->string('device_name')->toString()),
            'token_type' => 'Bearer',
            'expires_in' => config()->integer('ticko.token_ttl_minutes') * 60,
        ], 201);
    }

    /**
     * Revoke the current API token
     */
    #[SelfService]
    public function revokeToken(Request $request): Response
    {
        $token = $this->currentUser($request)->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->noContent();
    }

    /**
     * Current user
     */
    #[SelfService]
    #[ResponseFromApiResource(UserResource::class, User::class, with: ['tenant'])]
    public function me(Request $request): UserResource
    {
        return UserResource::make($this->currentUser($request)->load('tenant'));
    }

    /**
     * Change password
     *
     * Revokes every API token of the user except the one making the request.
     */
    #[SelfService]
    public function updatePassword(UpdatePasswordRequest $request): Response
    {
        $user = $this->currentUser($request);
        $user->forceFill(['password' => $request->string('password')->toString()])->save();

        $current = $user->currentAccessToken();
        $user->tokens()
            ->when($current instanceof PersonalAccessToken, fn ($q) => $q->whereKeyNot($current->getKey()))
            ->delete();

        return response()->noContent();
    }

    /**
     * Update notification preferences
     *
     * Chooses which events send a push notification to the user's devices.
     */
    #[SelfService]
    #[ResponseFromApiResource(UserResource::class, User::class, with: ['tenant'])]
    public function updateNotificationPreferences(UpdateNotificationPreferencesRequest $request): UserResource
    {
        $user = $this->currentUser($request);
        $user->forceFill([
            'push_replies' => $request->boolean('push_replies'),
            'push_status_changes' => $request->boolean('push_status_changes'),
        ])->save();

        return UserResource::make($user->load('tenant'));
    }
}
