<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Category;
use App\Models\Tenant;
use App\Models\User;

it('creates a tenant with a first admin', function (): void {
    $this->artisan('ticko:tenant:create', ['slug' => 'globex', 'name' => 'Globex', '--admin-email' => 'Boss@Globex.test'])
        ->expectsOutputToContain('Tenant [globex] created.')
        ->expectsOutputToContain('Password (shown once')
        ->assertSuccessful();

    $tenant = Tenant::where('slug', 'globex')->firstOrFail();
    $this->inTenant($tenant, function (): void {
        $admin = User::firstOrFail();
        expect($admin->email)->toBe('boss@globex.test')->and($admin->role)->toBe(Role::Admin);
    });
});

it('validates tenant creation input', function (): void {
    Tenant::factory()->create(['slug' => 'taken']);

    $this->artisan('ticko:tenant:create', ['slug' => 'taken', 'name' => 'X', '--admin-email' => 'x@x.test'])->assertFailed();
    $this->artisan('ticko:tenant:create', ['slug' => 'Bad Slug', 'name' => 'X', '--admin-email' => 'nope'])->assertFailed();
});

it('seeds a demo tenant once, with random passwords', function (): void {
    $this->artisan('ticko:demo')->expectsOutputToContain('X-Tenant: acme')->assertSuccessful();
    $this->artisan('ticko:demo')->expectsOutputToContain('already exists')->assertSuccessful();

    $tenant = Tenant::where('slug', 'acme')->firstOrFail();
    $this->inTenant($tenant, function (): void {
        expect(User::count())->toBe(3)->and(Category::count())->toBe(2);
    });
});

it('refuses to seed demo data in production', function (): void {
    $this->app['env'] = 'production';

    $this->artisan('ticko:demo')->assertFailed();
    expect(Tenant::count())->toBe(0);
});
