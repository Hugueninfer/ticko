<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\AiSuggestion;
use App\Models\Attachment;
use App\Models\Category;
use App\Models\KbArticle;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Images;

/*
| Every endpoint is exercised by an admin of tenant A against data that
| belongs to tenant B. Single-resource endpoints must answer 404 (not 403,
| which would confirm the resource exists); collections must not include
| foreign rows; writes must not touch tenant B.
*/

beforeEach(function (): void {
    Storage::fake('local');

    $this->tenantA = Tenant::factory()->create(['slug' => 'tenant-a']);
    $this->tenantB = Tenant::factory()->create(['slug' => 'tenant-b']);
    $this->adminA = $this->userIn($this->tenantA, Role::Admin);
    $this->adminB = $this->userIn($this->tenantB, Role::Admin);
    $this->agentB = $this->userIn($this->tenantB, Role::Agent);

    $this->b = $this->inTenant($this->tenantB, function (): array {
        $category = Category::factory()->create();
        $ticket = Ticket::factory()->create(['requester_id' => $this->adminB->id, 'category_id' => $category->id]);
        TicketMessage::factory()->create(['ticket_id' => $ticket->id, 'author_id' => $this->adminB->id]);
        $attachment = Attachment::factory()->create(['ticket_id' => $ticket->id, 'uploader_id' => $this->adminB->id]);
        $article = KbArticle::factory()->create(['author_id' => $this->adminB->id]);
        $suggestion = AiSuggestion::factory()->create(['ticket_id' => $ticket->id, 'requested_by_id' => $this->adminB->id]);

        return compact('category', 'ticket', 'attachment', 'article', 'suggestion');
    });
});

const FOREIGN_ENDPOINTS = [
    'show ticket' => ['GET', '/api/v1/tickets/{ticket}', []],
    'change ticket status' => ['PATCH', '/api/v1/tickets/{ticket}/status', ['status' => 'closed']],
    'assign ticket' => ['PATCH', '/api/v1/tickets/{ticket}/assignee', ['assignee_id' => null]],
    'list messages' => ['GET', '/api/v1/tickets/{ticket}/messages', []],
    'post message' => ['POST', '/api/v1/tickets/{ticket}/messages', ['body' => 'hi']],
    'list attachments' => ['GET', '/api/v1/tickets/{ticket}/attachments', []],
    'upload attachment' => ['POST', '/api/v1/tickets/{ticket}/attachments', ['file' => 'image']],
    'show attachment' => ['GET', '/api/v1/attachments/{attachment}', []],
    'show category' => ['GET', '/api/v1/categories/{category}', []],
    'update category' => ['PATCH', '/api/v1/categories/{category}', ['name' => 'Hijacked']],
    'replace category' => ['PUT', '/api/v1/categories/{category}', ['name' => 'Hijacked']],
    'delete category' => ['DELETE', '/api/v1/categories/{category}', []],
    'show member' => ['GET', '/api/v1/team/{member}', []],
    'update member' => ['PATCH', '/api/v1/team/{member}', ['role' => 'customer']],
    'show article' => ['GET', '/api/v1/knowledge/articles/{article}', []],
    'update article' => ['PATCH', '/api/v1/knowledge/articles/{article}', ['title' => 'Hijacked']],
    'replace article' => ['PUT', '/api/v1/knowledge/articles/{article}', ['title' => 'Hijacked']],
    'delete article' => ['DELETE', '/api/v1/knowledge/articles/{article}', []],
    'publish article' => ['POST', '/api/v1/knowledge/articles/{article}/publish', []],
    'unpublish article' => ['POST', '/api/v1/knowledge/articles/{article}/unpublish', []],
    'reindex article' => ['POST', '/api/v1/knowledge/articles/{article}/reindex', []],
    'article chunks' => ['GET', '/api/v1/knowledge/articles/{article}/chunks', []],
    'latest suggestion' => ['GET', '/api/v1/tickets/{ticket}/suggestions/latest', []],
    'request suggestion' => ['POST', '/api/v1/tickets/{ticket}/suggestions', []],
    'approve suggestion' => ['POST', '/api/v1/suggestions/{suggestion}/approve', ['body' => 'hi']],
    'discard suggestion' => ['POST', '/api/v1/suggestions/{suggestion}/discard', ['reason' => 'tone']],
    'report gap' => ['POST', '/api/v1/suggestions/{suggestion}/gap', []],
];

dataset('foreign single-resource endpoints', FOREIGN_ENDPOINTS);

it('hides other tenants\' resources', function (string $method, string $uri, array $payload): void {
    $uri = strtr($uri, [
        '{ticket}' => $this->b['ticket']->id,
        '{attachment}' => $this->b['attachment']->id,
        '{category}' => $this->b['category']->id,
        '{member}' => $this->agentB->id,
        '{article}' => $this->b['article']->id,
        '{suggestion}' => $this->b['suggestion']->id,
    ]);

    if (($payload['file'] ?? null) === 'image') {
        $payload['file'] = Images::png();
    }

    $this->actingAsUser($this->adminA)
        ->json($method, $uri, $payload)
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('detail', 'The requested resource does not exist.');

    // Nothing in tenant B changed.
    $this->inTenant($this->tenantB, function (): void {
        expect(Ticket::count())->toBe(1)
            ->and(TicketMessage::count())->toBe(1)
            ->and(Attachment::count())->toBe(1)
            ->and(Category::firstOrFail()->name)->toBe($this->b['category']->name)
            ->and($this->agentB->fresh()->role)->toBe(Role::Agent)
            ->and($this->b['ticket']->fresh()->status->value)->toBe('open')
            ->and($this->b['article']->fresh()->title)->toBe($this->b['article']->title)
            ->and($this->b['suggestion']->fresh()->status->value)->toBe('ready')
            ->and(AiSuggestion::count())->toBe(1);
    });
})->with('foreign single-resource endpoints');

it('never lists other tenants\' rows', function (string $uri, int $expected): void {
    $uri = str_replace('{requester}', $this->adminB->id, $uri);

    $this->actingAsUser($this->adminA)->getJson($uri)->assertOk()->assertJsonCount($expected, 'data');
})->with([
    'tickets' => ['/api/v1/tickets', 0],
    'tickets filtered by a foreign requester' => ['/api/v1/tickets?requester_id={requester}', 0],
    'overdue tickets' => ['/api/v1/tickets?overdue=1', 0],
    'categories' => ['/api/v1/categories', 0],
    'team (only the caller)' => ['/api/v1/team', 1],
    'knowledge articles' => ['/api/v1/knowledge/articles', 0],
]);

it('creates new rows in the caller\'s tenant only', function (): void {
    $this->actingAsUser($this->adminA)->postJson('/api/v1/tickets', ['subject' => 'Mine', 'description' => 'x'])->assertCreated();
    $this->postJson('/api/v1/categories', ['name' => 'Mine'])->assertCreated();
    $this->postJson('/api/v1/team', ['name' => 'Mine', 'email' => 'mine@a.test', 'password' => 'a-long-and-unique-passphrase', 'role' => 'agent'])->assertCreated();

    $this->inTenant($this->tenantB, function (): void {
        expect(Ticket::count())->toBe(1)->and(Category::count())->toBe(1);
    });
    $this->inTenant($this->tenantA, function (): void {
        expect(Ticket::count())->toBe(1)->and(Category::count())->toBe(1);
    });
});

it('refuses to assign a ticket to a user of another tenant', function (): void {
    $ticket = $this->inTenant($this->tenantA, fn () => Ticket::factory()->create(['requester_id' => $this->adminA->id]));

    $this->actingAsUser($this->adminA)
        ->patchJson("/api/v1/tickets/{$ticket->id}/assignee", ['assignee_id' => $this->agentB->id])
        ->assertUnprocessable();
});

it('exercises every API route that targets a single resource', function (): void {
    // Routes without a model in the URI are covered by the listing/creation tests above
    // and by the authentication tests; signed content URLs are covered in AttachmentTest.
    $covered = collect(FOREIGN_ENDPOINTS)->map(fn (array $e): string => $e[0].' '.ltrim($e[1], '/'));

    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_starts_with($route->uri(), 'api/') && str_contains($route->uri(), '{'))
        ->reject(fn ($route): bool => in_array('signed', $route->gatherMiddleware(), true))
        ->flatMap(fn ($route): array => collect($route->methods())
            ->reject(fn (string $m): bool => $m === 'HEAD')
            ->map(fn (string $m): string => $m.' '.$route->uri())
            ->all());

    expect($routes->diff($covered)->values()->all())->toBe([]);
});
