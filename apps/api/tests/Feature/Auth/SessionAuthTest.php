<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Tenant;
use Database\Factories\UserFactory;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create(['slug' => 'acme']);
    $this->user = $this->userIn($this->tenant, Role::Customer, ['email' => 'customer@acme.test']);
    // Requests from the configured SPA origin get Sanctum's stateful (cookie) treatment.
    $this->spa = ['Referer' => 'http://spa.test/login', 'Origin' => 'http://spa.test'];
});

it('logs in the SPA with a session that carries the tenant', function (): void {
    $this->withHeaders([...$this->spa, 'X-Tenant' => 'acme'])
        ->postJson('/api/v1/auth/login', ['email' => 'customer@acme.test', 'password' => UserFactory::PASSWORD])
        ->assertOk()
        ->assertJsonPath('data.email', 'customer@acme.test')
        ->assertSessionHas('tenant_id', $this->tenant->id);

    // Later requests need no tenant header: the session provides it.
    $this->withHeaders($this->spa)->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.id', $this->user->id);

    $this->withHeaders($this->spa)->postJson('/api/v1/auth/logout')->assertNoContent();

    $this->app['auth']->forgetGuards();
    $this->withHeaders($this->spa)->getJson('/api/v1/auth/me')->assertUnauthorized();
});

it('refuses session login from non first-party origins', function (): void {
    $this->withHeaders(['X-Tenant' => 'acme'])
        ->postJson('/api/v1/auth/login', ['email' => 'customer@acme.test', 'password' => UserFactory::PASSWORD])
        ->assertStatus(400)
        ->assertJsonPath('type', 'https://ticko.dev/problems/http-400');
});

it('does not reveal whether the account exists', function (): void {
    $known = $this->withHeaders([...$this->spa, 'X-Tenant' => 'acme'])
        ->postJson('/api/v1/auth/login', ['email' => 'customer@acme.test', 'password' => 'not-the-password'])
        ->assertUnprocessable()
        ->json();

    $unknown = $this->withHeaders([...$this->spa, 'X-Tenant' => 'acme'])
        ->postJson('/api/v1/auth/login', ['email' => 'ghost@acme.test', 'password' => 'not-the-password'])
        ->assertUnprocessable()
        ->json();

    expect($known)->toEqual($unknown);
});
