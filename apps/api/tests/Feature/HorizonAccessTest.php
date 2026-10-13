<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Tenant;
use Illuminate\Support\Facades\Gate;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
});

it('never lets guests into Horizon, not even locally', function (): void {
    $this->app['env'] = 'local';

    $this->get('/horizon')->assertForbidden();
});

it('only lets admins view Horizon', function (Role $role, bool $allowed): void {
    $this->app['env'] = 'local';
    $user = $this->userIn($this->tenant, $role);

    expect(Gate::forUser($user)->check('viewHorizon'))->toBe($allowed);
})->with([
    [Role::Customer, false],
    [Role::Agent, false],
    [Role::Admin, true],
]);

it('requires an allow-listed admin outside the local environment', function (): void {
    $admin = $this->userIn($this->tenant, Role::Admin, ['email' => 'ops@example.test']);
    $inactive = $this->userIn($this->tenant, Role::Admin, ['is_active' => false]);

    expect(Gate::forUser($admin)->check('viewHorizon'))->toBeFalse();

    config(['ticko.horizon_admins' => ['ops@example.test']]);
    expect(Gate::forUser($admin)->check('viewHorizon'))->toBeTrue()
        ->and(Gate::forUser($inactive)->check('viewHorizon'))->toBeFalse();
});

it('lets a logged-in admin open the dashboard through the web session', function (): void {
    $this->app['env'] = 'local';
    $admin = $this->userIn($this->tenant, Role::Admin);

    $this->withSession(['tenant_id' => $this->tenant->id])
        ->actingAs($admin, 'web')
        ->get('/horizon')
        ->assertOk();
});
