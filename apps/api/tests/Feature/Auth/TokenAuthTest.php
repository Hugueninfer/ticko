<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\PersonalAccessToken;
use App\Models\Tenant;
use Database\Factories\UserFactory;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create(['slug' => 'acme']);
    $this->user = $this->userIn($this->tenant, Role::Agent, ['email' => 'agent@acme.test']);
});

function tokenRequest(array $overrides = [], string $tenant = 'acme'): array
{
    return [
        'payload' => [
            'email' => 'agent@acme.test',
            'password' => UserFactory::PASSWORD,
            'device_name' => 'Pixel 9',
            ...$overrides,
        ],
        'headers' => ['X-Tenant' => $tenant],
    ];
}

it('issues a tenant-bound bearer token for valid credentials', function (): void {
    ['payload' => $payload, 'headers' => $headers] = tokenRequest();

    $response = $this->postJson('/api/v1/auth/tokens', $payload, $headers)
        ->assertCreated()
        ->assertJsonStructure(['token', 'token_type', 'expires_in']);

    // Sanctum format "<id>|<prefix><secret>"; the prefix lets secret scanners spot leaked tokens.
    expect(explode('|', $response->json('token'), 2)[1])->toStartWith(config('sanctum.token_prefix'));

    $token = PersonalAccessToken::findToken($response->json('token'));
    expect($token)->not->toBeNull()
        ->and($token->tenant_id)->toBe($this->tenant->id)
        ->and($token->expires_at)->not->toBeNull();

    $this->app['auth']->forgetGuards();
    $this->withToken($response->json('token'))->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.id', $this->user->id);
});

it('accepts e-mails regardless of case', function (): void {
    ['payload' => $payload, 'headers' => $headers] = tokenRequest(['email' => 'AGENT@acme.test']);

    $this->postJson('/api/v1/auth/tokens', $payload, $headers)->assertCreated();
});

it('returns the same generic error for every kind of failure', function (array $overrides, string $tenant): void {
    ['payload' => $payload, 'headers' => $headers] = tokenRequest($overrides, $tenant);

    $this->postJson('/api/v1/auth/tokens', $payload, $headers)
        ->assertUnprocessable()
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('errors.email.0', __('auth.failed'));
})->with([
    'wrong password' => [['password' => 'wrong-password-123'], 'acme'],
    'unknown e-mail' => [['email' => 'nobody@acme.test'], 'acme'],
    'unknown tenant' => [[], 'does-not-exist'],
]);

it('rejects users of another tenant even with the right password', function (): void {
    Tenant::factory()->create(['slug' => 'globex']);
    ['payload' => $payload] = tokenRequest();

    $this->postJson('/api/v1/auth/tokens', $payload, ['X-Tenant' => 'globex'])->assertUnprocessable();
});

it('rejects deactivated users with the generic error', function (): void {
    $this->inTenant($this->tenant, fn () => $this->user->update(['is_active' => false]));
    ['payload' => $payload, 'headers' => $headers] = tokenRequest();

    $this->postJson('/api/v1/auth/tokens', $payload, $headers)
        ->assertUnprocessable()
        ->assertJsonPath('errors.email.0', __('auth.failed'));
});

it('treats tokens of deactivated users as unauthenticated', function (): void {
    $this->actingAsUser($this->user);
    $this->inTenant($this->tenant, fn () => $this->user->forceFill(['is_active' => false])->save());

    $this->getJson('/api/v1/auth/me')->assertUnauthorized();
});

it('rejects deactivated users before route model binding, so ids cannot be probed', function (): void {
    $this->actingAsUser($this->user);
    $this->inTenant($this->tenant, fn () => $this->user->forceFill(['is_active' => false])->save());

    $this->getJson('/api/v1/tickets/'.Str::ulid())->assertUnauthorized();
});

it('rejects expired tokens', function (): void {
    $this->actingAsUser($this->user);
    PersonalAccessToken::query()->update(['expires_at' => now()->subMinute()]);

    $this->getJson('/api/v1/auth/me')->assertUnauthorized();
});

it('revokes the current token', function (): void {
    $this->actingAsUser($this->user)->deleteJson('/api/v1/auth/tokens/current')->assertNoContent();

    expect(PersonalAccessToken::count())->toBe(0);

    $this->app['auth']->forgetGuards();
    $this->getJson('/api/v1/auth/me')->assertUnauthorized();
});

it('requires authentication on protected endpoints', function (): void {
    $this->getJson('/api/v1/auth/me')
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJson(['status' => 401, 'title' => 'Unauthenticated.']);
});

it('ignores the X-Tenant header when a token is present', function (): void {
    $other = Tenant::factory()->create(['slug' => 'globex']);
    $this->userIn($other, Role::Admin);

    $this->actingAsUser($this->user)
        ->withHeader('X-Tenant', 'globex')
        ->getJson('/api/v1/team')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $this->user->id);
});
