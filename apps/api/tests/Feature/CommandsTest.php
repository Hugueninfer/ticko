<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Jobs\IndexKnowledgeArticle;
use App\Models\Category;
use App\Models\KbArticle;
use App\Models\KbChunk;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

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
        expect(User::count())->toBe(3)
            ->and(Category::count())->toBe(4)
            ->and(Ticket::count())->toBe(8)
            ->and(Ticket::whereNotNull('first_response_at')->count())->toBe(2)
            ->and(Ticket::overdue()->count())->toBeGreaterThan(0)
            // Knowledge base: 7 published (indexed by the synchronous test queue) and 1 draft.
            ->and(KbArticle::count())->toBe(8)
            ->and(KbArticle::where('index_status', 'indexed')->count())->toBe(7)
            ->and(KbChunk::count())->toBeGreaterThan(7);
    });
});

it('adds the knowledge base to an existing demo tenant once', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'acme']);
    $this->userIn($tenant, Role::Admin);

    $this->artisan('ticko:demo')->expectsOutputToContain('Added 8 knowledge-base articles')->assertSuccessful();
    $this->artisan('ticko:demo')->expectsOutputToContain('Nothing to do')->assertSuccessful();

    $this->inTenant($tenant, fn () => expect(KbArticle::count())->toBe(8));
});

it('refuses to seed demo data in production', function (): void {
    $this->app['env'] = 'production';

    $this->artisan('ticko:demo')->assertFailed();
    expect(Tenant::count())->toBe(0);
});

it('reindexes every published article, per tenant if asked', function (): void {
    Queue::fake();
    $acme = Tenant::factory()->create(['slug' => 'acme']);
    $globex = Tenant::factory()->create(['slug' => 'globex']);

    foreach ([$acme, $globex] as $tenant) {
        $author = $this->userIn($tenant, Role::Agent);
        $this->inTenant($tenant, function () use ($author): void {
            KbArticle::factory()->create(['author_id' => $author->id])->forceFill(['status' => 'published'])->save();
            KbArticle::factory()->create(['author_id' => $author->id]);
        });
    }

    $this->artisan('ticko:kb:reindex')->expectsOutputToContain('Queued 2 articles')->assertSuccessful();
    $this->artisan('ticko:kb:reindex', ['--tenant' => 'globex'])->expectsOutputToContain('Queued 1 articles')->assertSuccessful();

    Queue::assertPushed(IndexKnowledgeArticle::class, 3);
});
