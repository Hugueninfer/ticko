<?php

declare(strict_types=1);

use App\Enums\ArticleStatus;
use App\Enums\IndexStatus;
use App\Enums\Role;
use App\Models\Category;
use App\Models\KbArticle;
use App\Models\KbChunk;
use App\Models\Tenant;
use App\Services\Ai\AiUnavailable;
use App\Services\Ai\Embedder;
use App\Services\Ai\EmbeddingTask;
use Illuminate\Http\UploadedFile;
use Tests\Support\Knowledge;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    $this->agent = $this->userIn($this->tenant, Role::Agent);
    $this->admin = $this->userIn($this->tenant, Role::Admin);
    $this->customer = $this->userIn($this->tenant, Role::Customer);
});

function articlePayload(array $overrides = []): array
{
    return ['title' => 'Política de entregas e prazos', 'body' => Knowledge::DELIVERY, 'visibility' => 'customers', 'tags' => ['rastreio', 'prazo'], ...$overrides];
}

it('lets agents write, publish and index articles', function (): void {
    $id = $this->actingAsUser($this->agent)
        ->postJson('/api/v1/knowledge/articles', articlePayload())
        ->assertCreated()
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.index.status', 'none')
        ->assertJsonPath('data.body', Knowledge::DELIVERY)
        ->json('data.id');

    // Drafts are never indexed.
    $this->inTenant($this->tenant, fn () => expect(KbChunk::count())->toBe(0));

    $this->postJson("/api/v1/knowledge/articles/{$id}/publish")
        ->assertOk()
        ->assertJsonPath('data.status', 'published');

    $this->getJson("/api/v1/knowledge/articles/{$id}")
        ->assertJsonPath('data.index.status', 'indexed')
        ->assertJsonPath('data.index.chunks_count', 2)
        ->assertJsonPath('data.has_unpublished_changes', false);

    $this->getJson("/api/v1/knowledge/articles/{$id}/chunks")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.heading', 'Prazos por região')
        ->assertJsonPath('data.1.heading', 'Rastreio sem atualização')
        ->assertJsonPath('meta.embedding_model', 'fake/hashed-stems-768')
        ->assertJsonMissingPath('data.0.embedding');
});

it('keeps citing the published version while a draft is edited', function (): void {
    $article = Knowledge::publish($this->tenant, $this->agent, 'Entregas', Knowledge::DELIVERY);

    $this->actingAsUser($this->agent)
        ->patchJson("/api/v1/knowledge/articles/{$article->id}", ['body' => "## Novo\nPrazo novo de 1 dia."])
        ->assertOk()
        ->assertJsonPath('data.has_unpublished_changes', true);

    $this->inTenant($this->tenant, function () use ($article): void {
        expect(KbChunk::where('article_id', $article->id)->pluck('heading')->all())->toContain('Rastreio sem atualização');
    });

    $this->postJson("/api/v1/knowledge/articles/{$article->id}/publish")->assertOk();

    $this->inTenant($this->tenant, function () use ($article): void {
        expect(KbChunk::where('article_id', $article->id)->pluck('heading')->all())->toBe(['Novo']);
    });
});

it('removes an unpublished article from the AI at once', function (): void {
    $article = Knowledge::publish($this->tenant, $this->agent, 'Entregas', Knowledge::DELIVERY);

    $this->actingAsUser($this->agent)
        ->postJson("/api/v1/knowledge/articles/{$article->id}/unpublish")
        ->assertOk()
        ->assertJsonPath('data.status', 'archived')
        ->assertJsonPath('data.index.status', 'none');

    $this->inTenant($this->tenant, fn () => expect(KbChunk::count())->toBe(0));

    $this->postJson("/api/v1/knowledge/articles/{$article->id}/reindex")->assertUnprocessable();
});

it('marks failed indexing and indexes again on request', function (): void {
    app()->instance(Embedder::class, new class implements Embedder
    {
        public function embed(array $texts, EmbeddingTask $task): array
        {
            throw AiUnavailable::timeout();
        }

        public function embeddingModel(): string
        {
            return 'broken';
        }
    });

    $article = Knowledge::publish($this->tenant, $this->agent, 'Entregas', Knowledge::DELIVERY);

    expect($article->index_status)->toBe(IndexStatus::Failed)->and($article->index_error)->toBe('LLM_TIMEOUT');

    $this->actingAsUser($this->agent)->getJson('/api/v1/knowledge/stats')->assertJsonPath('data.failed', 1);

    app()->forgetInstance(Embedder::class);

    $this->postJson("/api/v1/knowledge/articles/{$article->id}/reindex")->assertOk();
    $this->getJson("/api/v1/knowledge/articles/{$article->id}")->assertJsonPath('data.index.status', 'indexed');
});

it('lists articles with filters and figures', function (): void {
    $category = $this->inTenant($this->tenant, fn () => Category::factory()->create());
    Knowledge::publish($this->tenant, $this->agent, 'Política de entregas', Knowledge::DELIVERY, categoryId: $category->id);
    $this->actingAsUser($this->agent)->postJson('/api/v1/knowledge/articles', articlePayload(['title' => 'Cupons vigentes']))->assertCreated();

    $this->getJson('/api/v1/knowledge/articles')->assertOk()->assertJsonCount(2, 'data')->assertJsonMissingPath('data.0.body');
    $this->getJson('/api/v1/knowledge/articles?status=draft')->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Cupons vigentes');
    $this->getJson('/api/v1/knowledge/articles?q=entregas')->assertJsonCount(1, 'data');
    $this->getJson("/api/v1/knowledge/articles?category_id={$category->id}")->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.category.id', $category->id);
    $this->getJson('/api/v1/knowledge/articles?index_status=indexed')->assertJsonCount(1, 'data');

    $this->getJson('/api/v1/knowledge/stats')
        ->assertOk()
        ->assertExactJson(['data' => ['published' => 1, 'chunks' => 2, 'queued' => 0, 'failed' => 0, 'gaps' => 0]]);
});

it('imports a Markdown file as a draft', function (): void {
    $file = UploadedFile::fake()->createWithContent('trocas.md', "# Trocas e devoluções\n\n".Knowledge::RETURNS);

    $this->actingAsUser($this->agent)
        ->post('/api/v1/knowledge/articles/import', ['file' => $file])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Trocas e devoluções')
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.body', trim(Knowledge::RETURNS));
});

it('rejects imports that are not text', function (): void {
    $this->actingAsUser($this->agent)
        ->post('/api/v1/knowledge/articles/import', ['file' => UploadedFile::fake()->image('photo.png')])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');
});

it('validates articles', function (array $payload, string $field): void {
    $category = Tenant::factory()->create();
    $foreignCategory = $this->inTenant($category, fn () => Category::factory()->create());

    $this->actingAsUser($this->agent)
        ->postJson('/api/v1/knowledge/articles', articlePayload(array_map(fn ($v) => $v === 'foreign' ? $foreignCategory->id : $v, $payload)))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'short title' => [['title' => 'ab'], 'title'],
    'missing body' => [['body' => ''], 'body'],
    'unknown visibility' => [['visibility' => 'public'], 'visibility'],
    'too many tags' => [['tags' => array_fill(0, 11, 'x')], 'tags'],
    'category of another tenant' => [['category_id' => 'foreign'], 'category_id'],
]);

it('keeps the knowledge base away from customers', function (): void {
    $article = Knowledge::publish($this->tenant, $this->agent, 'Entregas', Knowledge::DELIVERY);

    $this->actingAsUser($this->customer);
    $this->getJson('/api/v1/knowledge/articles')->assertForbidden();
    $this->getJson("/api/v1/knowledge/articles/{$article->id}")->assertForbidden();
    $this->getJson("/api/v1/knowledge/articles/{$article->id}/chunks")->assertForbidden();
    $this->postJson('/api/v1/knowledge/articles', articlePayload())->assertForbidden();
    $this->postJson('/api/v1/knowledge/search', ['query' => 'prazo'])->assertForbidden();
    $this->getJson('/api/v1/knowledge/stats')->assertForbidden();
});

it('lets only admins delete articles', function (): void {
    $article = Knowledge::publish($this->tenant, $this->agent, 'Entregas', Knowledge::DELIVERY);

    $this->actingAsUser($this->agent)->deleteJson("/api/v1/knowledge/articles/{$article->id}")->assertForbidden();
    $this->actingAsUser($this->admin)->deleteJson("/api/v1/knowledge/articles/{$article->id}")->assertNoContent();

    $this->inTenant($this->tenant, fn () => expect(KbArticle::count())->toBe(0)->and(KbChunk::count())->toBe(0));
});

it('keeps articles when their category is deleted', function (): void {
    $category = $this->inTenant($this->tenant, fn () => Category::factory()->create());
    $article = Knowledge::publish($this->tenant, $this->agent, 'Entregas', Knowledge::DELIVERY, categoryId: $category->id);

    $this->actingAsUser($this->admin)->deleteJson("/api/v1/categories/{$category->id}")->assertNoContent();

    $this->inTenant($this->tenant, function () use ($article): void {
        $fresh = $article->fresh();
        expect($fresh->category_id)->toBeNull()->and($fresh->status)->toBe(ArticleStatus::Published);
    });
});
