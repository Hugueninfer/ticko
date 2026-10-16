<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Jobs\ClassifyTicket;
use App\Jobs\IndexKnowledgeArticle;
use App\Models\AiSuggestion;
use App\Models\AuditEvent;
use App\Models\Category;
use App\Models\ClassificationCorrection;
use App\Models\KbArticle;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\TicketClassification;
use App\Models\TicketMessage;
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

it('seeds a coherent demo tenant through the real actions', function (): void {
    $this->artisan('ticko:demo')->expectsOutputToContain('X-Tenant: acme')->expectsOutputToContain('agent@acme.test')->assertSuccessful();

    $tenant = Tenant::where('slug', 'acme')->firstOrFail();
    $this->inTenant($tenant, function (): void {
        expect(User::count())->toBe(9)
            ->and(Category::count())->toBe(6)
            ->and(Ticket::count())->toBe(25)
            // Every state, overdue and on-time tickets, conversations and internal notes.
            ->and(Ticket::query()->distinct()->pluck('status')->map->value->sort()->values()->all())->toBe(['closed', 'open', 'pending', 'resolved'])
            ->and(Ticket::overdue()->count())->toBeGreaterThan(0)
            ->and(TicketMessage::where('is_internal', true)->count())->toBeGreaterThan(0)
            // What the customer chose is never overwritten by the classifier.
            ->and(Ticket::where('category_source', 'requester')->count())->toBe(17)
            // Knowledge base: 7 published (indexed) and 1 draft.
            ->and(KbArticle::count())->toBe(8)
            ->and(KbArticle::where('index_status', 'indexed')->count())->toBe(7)
            // AI suggestions in every outcome, reviewed through the real actions.
            ->and(AiSuggestion::query()->distinct()->pluck('status')->map->value->sort()->values()->all())
            ->toBe(['approved', 'discarded', 'failed', 'insufficient', 'ready'])
            ->and(AiSuggestion::where('edited', true)->count())->toBeGreaterThan(0)
            ->and(AiSuggestion::where('status', 'approved')->sum('input_tokens'))->toBeGreaterThan(0)
            // Classified and corrected, with the audit trail.
            ->and(TicketClassification::count())->toBe(25)
            ->and(ClassificationCorrection::count())->toBeGreaterThan(0)
            ->and(AuditEvent::where('action', 'ticket.classification_changed')->count())->toBe(3);
    });
});

it('is idempotent and can start over', function (): void {
    $this->artisan('ticko:demo')->assertSuccessful();
    $tenant = Tenant::where('slug', 'acme')->firstOrFail();
    $before = $this->inTenant($tenant, fn () => [Ticket::count(), AiSuggestion::count(), TicketMessage::count(), AuditEvent::count()]);

    $this->artisan('ticko:demo')->expectsOutputToContain('No new users')->assertSuccessful();
    expect($this->inTenant($tenant, fn () => [Ticket::count(), AiSuggestion::count(), TicketMessage::count(), AuditEvent::count()]))->toBe($before);

    // A missing ticket is added back on the next run.
    $this->inTenant($tenant, fn () => Ticket::where('subject', 'Elogio para a equipe de atendimento')->delete());
    $this->artisan('ticko:demo')->assertSuccessful();
    expect($this->inTenant($tenant, fn () => Ticket::count()))->toBe(25);

    $this->artisan('ticko:demo', ['--fresh' => true])->expectsOutputToContain('Deleted the demo tenant')->expectsOutputToContain('admin@acme.test')->assertSuccessful();
    $fresh = Tenant::where('slug', 'acme')->firstOrFail();
    expect($fresh->id)->not->toBe($tenant->id)
        ->and(Tenant::where('slug', 'acme')->count())->toBe(1)
        ->and($this->inTenant($fresh, fn () => Ticket::count()))->toBe(25);
});

it('queues the demo tickets for the real classifier when it is on', function (): void {
    config(['ticko.classifier.url' => 'http://classifier:8000']);
    Queue::fake([ClassifyTicket::class]);

    $this->artisan('ticko:demo')->assertSuccessful();

    Queue::assertPushed(ClassifyTicket::class, 25);
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
