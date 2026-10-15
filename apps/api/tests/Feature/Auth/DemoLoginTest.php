<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\PersonalAccessToken;
use App\Models\Tenant;

beforeEach(function (): void {
    config(['ticko.demo.enabled' => true, 'ticko.demo.tenant' => 'acme']);

    $this->demo = Tenant::factory()->create(['slug' => 'acme']);
    $this->agent = $this->userIn($this->demo, Role::Agent);
    $this->admin = $this->userIn($this->demo, Role::Admin);
    $this->spa = ['Referer' => 'http://spa.test/login', 'Origin' => 'http://spa.test'];
});

it('tells the web app that demo sign-in is available', function (): void {
    $this->getJson('/api/v1/auth/demo')
        ->assertOk()
        ->assertExactJson(['data' => ['tenant' => 'acme', 'roles' => ['agent', 'admin']]]);
});

it('signs the SPA in as the demo agent or admin, in the demo tenant', function (Role $role): void {
    $other = Tenant::factory()->create(['slug' => 'other']);

    $this->withHeaders([...$this->spa, 'X-Tenant' => 'other'])
        ->postJson('/api/v1/auth/demo', ['role' => $role->value])
        ->assertOk()
        ->assertJsonPath('data.role', $role->value)
        ->assertJsonPath('data.tenant.slug', 'acme')
        ->assertSessionHas('tenant_id', $this->demo->id);

    expect($other->id)->not->toBe($this->demo->id);

    $this->withHeaders($this->spa)->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.role', $role->value)
        ->assertJsonPath('data.tenant.name', $this->demo->name);
})->with([Role::Agent, Role::Admin]);

it('does not offer demo customers or unknown roles', function (string $role): void {
    $this->withHeaders($this->spa)
        ->postJson('/api/v1/auth/demo', ['role' => $role])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('role');
})->with(['customer', 'root']);

it('requires the first-party web app session', function (): void {
    $this->postJson('/api/v1/auth/demo', ['role' => 'agent'])->assertStatus(400);
});

it('looks absent when disabled, in production, or without a demo tenant', function (Closure $setup): void {
    $setup();

    $this->getJson('/api/v1/auth/demo')->assertNotFound();
    // Outside the testing environment CSRF protection may answer first (419); either way nobody is signed in.
    $status = $this->withHeaders($this->spa)->postJson('/api/v1/auth/demo', ['role' => 'agent'])->status();
    expect($status)->toBeIn([404, 419]);
    $this->getJson('/api/v1/auth/me')->assertUnauthorized();
})->with([
    'flag off' => [fn () => config(['ticko.demo.enabled' => false])],
    'production' => [fn () => app()->detectEnvironment(fn (): string => 'production')],
    'missing tenant' => [fn () => config(['ticko.demo.tenant' => 'nope'])],
]);

it('is rate limited per client, separately from credential sign-in', function (): void {
    config(['ticko.rate_limits.demo' => 2, 'ticko.rate_limits.auth' => 1]);

    $this->withHeaders($this->spa)->postJson('/api/v1/auth/demo', ['role' => 'root'])->assertUnprocessable();
    $this->withHeaders($this->spa)->postJson('/api/v1/auth/demo', ['role' => 'root'])->assertUnprocessable();
    $this->withHeaders($this->spa)->postJson('/api/v1/auth/demo', ['role' => 'root'])->assertTooManyRequests();
});

it('issues a token for the demo customer to the mobile app', function (): void {
    $customer = $this->userIn($this->demo, Role::Customer);
    $this->userIn($this->demo, Role::Customer);

    $token = $this->withHeader('X-Tenant', 'other')
        ->postJson('/api/v1/auth/demo/tokens', ['device_name' => 'Pixel 9'])
        ->assertCreated()
        ->assertJsonStructure(['token', 'token_type', 'expires_in'])
        ->json('token');

    $this->app['auth']->forgetGuards();
    $this->withToken($token)->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.id', $customer->id)
        ->assertJsonPath('data.role', 'customer')
        ->assertJsonPath('data.tenant.slug', 'acme');
});

it('validates the demo token request and needs a demo customer', function (): void {
    $this->postJson('/api/v1/auth/demo/tokens', [])->assertUnprocessable()->assertJsonValidationErrors('device_name');
    $this->postJson('/api/v1/auth/demo/tokens', ['device_name' => 'Pixel'])->assertNotFound();
});

it('never issues demo tokens when demo sign-in is disabled or in production', function (Closure $setup): void {
    $this->userIn($this->demo, Role::Customer);
    $setup();

    $this->postJson('/api/v1/auth/demo/tokens', ['device_name' => 'Pixel'])->assertNotFound();
    expect(PersonalAccessToken::count())->toBe(0);
})->with([
    'disabled' => [fn () => config(['ticko.demo.enabled' => false])],
    'production' => [fn () => app()->detectEnvironment(fn (): string => 'production')],
]);
