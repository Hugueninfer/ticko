<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Category;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Support\Tenancy\MissingTenantException;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/*
| These tests bypass Eloquent on purpose: they prove the database itself
| enforces isolation, so a missing global scope or a raw query cannot leak.
*/

beforeEach(function (): void {
    $this->a = Tenant::factory()->create();
    $this->b = Tenant::factory()->create();
    $this->userIn($this->a, Role::Agent);
    $this->userIn($this->b, Role::Agent);
    $this->userIn($this->b, Role::Customer);
});

it('connects with a role that cannot bypass RLS', function (): void {
    $role = DB::selectOne('select rolsuper, rolbypassrls from pg_roles where rolname = current_user');

    expect($role->rolsuper)->toBeFalse()->and($role->rolbypassrls)->toBeFalse();
});

it('forces RLS on every tenant-owned table', function (): void {
    $tables = DB::select(<<<'SQL'
        select c.relname, c.relrowsecurity, c.relforcerowsecurity
        from pg_class c
        join pg_attribute a on a.attrelid = c.oid and a.attname = 'tenant_id' and not a.attisdropped
        where c.relkind = 'r' and c.relnamespace = 'public'::regnamespace
        SQL);

    $unprotected = collect($tables)
        ->reject(fn ($t): bool => $t->relrowsecurity && $t->relforcerowsecurity)
        ->pluck('relname')
        ->all();

    // Tokens must be readable before the tenant is known; they hold only hashes.
    expect($unprotected)->toBe(['personal_access_tokens']);
});

it('shows raw queries only the rows of the current tenant', function (): void {
    expect(DB::table('users')->count())->toBe(0);

    app(TenantContext::class)->run($this->a, fn () => expect(DB::table('users')->count())->toBe(1));
    app(TenantContext::class)->run($this->b, fn () => expect(DB::table('users')->count())->toBe(2));

    expect(DB::scalar("select current_setting('app.tenant_id', true)"))->toBe('');
});

it('rejects raw writes into another tenant', function (): void {
    $categoryOfB = app(TenantContext::class)->run($this->b, fn () => Category::factory()->create());

    app(TenantContext::class)->run($this->a, function () use ($categoryOfB): void {
        expect(DB::table('categories')->where('id', $categoryOfB->id)->update(['name' => 'pwned']))->toBe(0)
            ->and(DB::table('categories')->where('id', $categoryOfB->id)->delete())->toBe(0);

        // Nested transaction = savepoint, so the expected failure does not abort the test transaction.
        expect(fn () => DB::transaction(fn () => DB::table('categories')->insert([
            'id' => (string) str()->ulid(), 'tenant_id' => $this->b->id, 'name' => 'x', 'slug' => 'x',
            'created_at' => now(), 'updated_at' => now(),
        ])))->toThrow(QueryException::class, 'row-level security');
    });

    app(TenantContext::class)->run($this->b, fn () => expect(Category::firstOrFail()->name)->toBe($categoryOfB->name));
});

it('blocks cross-tenant references with composite foreign keys', function (): void {
    $customerOfB = app(TenantContext::class)->run($this->b, fn () => App\Models\User::query()->where('role', 'customer')->firstOrFail());

    app(TenantContext::class)->run($this->a, function () use ($customerOfB): void {
        expect(fn () => DB::transaction(fn () => Ticket::factory()->create(['requester_id' => $customerOfB->id])))
            ->toThrow(QueryException::class, 'foreign key');
    });
});

it('refuses to create tenant-owned models without a tenant context', function (): void {
    expect(fn () => Category::factory()->create())->toThrow(MissingTenantException::class);
});

it('scopes Eloquent queries to nothing without a tenant context', function (): void {
    expect(App\Models\User::count())->toBe(0)
        ->and(App\Models\User::query()->toRawSql())->toContain('1 = 0');
});
