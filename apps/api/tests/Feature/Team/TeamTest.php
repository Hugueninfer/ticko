<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Enums\TicketStatus;
use App\Models\PersonalAccessToken;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    $this->admin = $this->userIn($this->tenant, Role::Admin, ['name' => 'Alice']);
    $this->agent = $this->userIn($this->tenant, Role::Agent, ['name' => 'Bob']);
    $this->customer = $this->userIn($this->tenant, Role::Customer, ['name' => 'Carol']);
});

it('lets staff list and view the team', function (string $who): void {
    $this->actingAsUser($this->{$who});

    $this->getJson('/api/v1/team')->assertOk()->assertJsonCount(3, 'data')->assertJsonMissingPath('data.0.password');
    $this->getJson('/api/v1/team?role=agent')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/team?role=root')->assertUnprocessable();
    $this->getJson("/api/v1/team/{$this->customer->id}")->assertOk()->assertJsonPath('data.role', 'customer');
})->with(['admin', 'agent']);

it('forbids customers from seeing the team', function (): void {
    $this->actingAsUser($this->customer);

    $this->getJson('/api/v1/team')->assertForbidden();
    $this->getJson("/api/v1/team/{$this->agent->id}")->assertForbidden();
});

it('lets admins add members with a hashed password', function (): void {
    $this->actingAsUser($this->admin)->postJson('/api/v1/team', [
        'name' => 'Dave',
        'email' => 'Dave@Example.test',
        'password' => 'a-long-and-unique-passphrase',
        'role' => 'agent',
        'tenant_id' => Tenant::factory()->create()->id, // ignored
    ])
        ->assertCreated()
        ->assertJsonPath('data.email', 'dave@example.test')
        ->assertJsonPath('data.role', 'agent');

    $this->inTenant($this->tenant, function (): void {
        $dave = User::where('email', 'dave@example.test')->firstOrFail();
        expect($dave->tenant_id)->toBe($this->tenant->id)
            ->and(Hash::check('a-long-and-unique-passphrase', $dave->password))->toBeTrue();
    });
});

it('validates new members', function (): void {
    $this->actingAsUser($this->admin)->postJson('/api/v1/team', [
        'name' => '',
        'email' => strtoupper($this->agent->email),
        'password' => 'short',
        'role' => 'superadmin',
    ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password', 'role']);
});

it('allows the same e-mail in different tenants', function (): void {
    $other = Tenant::factory()->create();
    $this->userIn($other, Role::Agent, ['email' => 'shared@example.test']);

    $this->actingAsUser($this->admin)->postJson('/api/v1/team', [
        'name' => 'Shared', 'email' => 'shared@example.test', 'password' => 'a-long-and-unique-passphrase', 'role' => 'customer',
    ])->assertCreated();
});

it('lets admins change roles and deactivate members, revoking their tokens', function (): void {
    $this->inTenant($this->tenant, fn () => $this->agent->createToken('phone'));

    $this->actingAsUser($this->admin)
        ->patchJson("/api/v1/team/{$this->agent->id}", ['role' => 'admin', 'is_active' => false, 'name' => 'Robert'])
        ->assertOk()
        ->assertJsonPath('data.role', 'admin')
        ->assertJsonPath('data.is_active', false)
        ->assertJsonPath('data.name', 'Robert');

    expect(PersonalAccessToken::where('tokenable_id', $this->agent->id)->count())->toBe(0);
});

it('keeps tokens when only the name changes', function (): void {
    $this->inTenant($this->tenant, fn () => $this->agent->createToken('phone'));

    $this->actingAsUser($this->admin)->patchJson("/api/v1/team/{$this->agent->id}", ['name' => 'Robert'])->assertOk();

    expect(PersonalAccessToken::where('tokenable_id', $this->agent->id)->count())->toBe(1);
});

it('does not let admins demote or deactivate themselves', function (): void {
    $this->actingAsUser($this->admin)
        ->patchJson("/api/v1/team/{$this->admin->id}", ['role' => 'customer'])
        ->assertForbidden();
});

it('forbids agents and customers from managing members', function (string $who): void {
    $this->actingAsUser($this->{$who});

    $this->postJson('/api/v1/team', [
        'name' => 'X', 'email' => 'x@example.test', 'password' => 'a-long-and-unique-passphrase', 'role' => 'admin',
    ])->assertForbidden();
    $this->patchJson("/api/v1/team/{$this->customer->id}", ['role' => 'admin'])->assertForbidden();
})->with(['agent', 'customer']);

it('searches members and counts their open assigned tickets', function (): void {
    $ana = $this->userIn($this->tenant, Role::Agent, ['name' => 'Ana Souza', 'email' => 'ana@acme.test']);
    $this->inTenant($this->tenant, function () use ($ana): void {
        Ticket::factory()->count(2)->create(['assignee_id' => $ana->id]);
        Ticket::factory()->status(TicketStatus::Closed)->create(['assignee_id' => $ana->id]);
    });

    $this->actingAsUser($this->admin)->getJson('/api/v1/team?q=souza')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $ana->id)
        ->assertJsonPath('data.0.open_tickets_count', 2);

    $this->getJson('/api/v1/team?q=%25')->assertOk()->assertJsonCount(0, 'data');
});
