<?php

declare(strict_types=1);

use App\Enums\ArticleVisibility;
use App\Enums\Role;
use App\Models\Category;
use App\Models\KbChunk;
use App\Models\Tenant;
use App\Services\Knowledge\KnowledgeSearch;
use App\Services\Knowledge\SearchOptions;
use App\Support\Tenancy\MissingTenantException;
use Tests\Support\Knowledge;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    $this->agent = $this->userIn($this->tenant, Role::Agent);
    $this->category = $this->inTenant($this->tenant, fn () => Category::factory()->create());
    $this->delivery = Knowledge::publish($this->tenant, $this->agent, 'Política de entregas e prazos', Knowledge::DELIVERY, categoryId: $this->category->id);
    $this->returns = Knowledge::publish($this->tenant, $this->agent, 'Trocas e devoluções', Knowledge::RETURNS);
});

it('finds the passage that answers the question first, with both scores', function (): void {
    $response = $this->actingAsUser($this->agent)
        ->postJson('/api/v1/knowledge/search', ['query' => 'o rastreamento não é atualizado há uma semana, o que faço?'])
        ->assertOk()
        ->assertJsonPath('data.0.article_id', $this->delivery->id)
        ->assertJsonPath('data.0.heading', 'Rastreio sem atualização')
        ->assertJsonPath('meta.embedding_model', 'fake/hashed-stems-768');

    $first = $response->json('data.0');
    expect($first['text_score'])->toBeGreaterThan(0)
        ->and($first['semantic_score'])->toBeGreaterThan(0)
        ->and($first['score'])->toBeLessThanOrEqual(1)
        ->and($response->json('meta.latency_ms'))->toBeInt();
});

it('honours top-k, the category filter and reciprocal rank fusion', function (): void {
    $this->actingAsUser($this->agent);

    $this->postJson('/api/v1/knowledge/search', ['query' => 'prazo de devolução e entrega', 'top_k' => 1])->assertJsonCount(1, 'data');
    $titles = collect($this->postJson('/api/v1/knowledge/search', ['query' => 'prazo de devolução', 'category_id' => $this->category->id])->json('data'))
        ->pluck('title')->unique()->values()->all();
    expect($titles)->toBe(['Política de entregas e prazos']);

    $fused = $this->postJson('/api/v1/knowledge/search', ['query' => 'rastreamento transportadora', 'rerank' => true])->assertOk()->json('data');
    expect($fused[0]['heading'])->toBe('Rastreio sem atualização')->and($fused[0]['score'])->toBeLessThanOrEqual(1);
});

it('never returns drafts, archived or other tenants\' articles', function (): void {
    $other = Tenant::factory()->create();
    $otherAgent = $this->userIn($other, Role::Agent);
    // Same text in another tenant: must never appear.
    Knowledge::publish($other, $otherAgent, 'Política de entregas e prazos', Knowledge::DELIVERY);

    $this->actingAsUser($this->agent)->postJson("/api/v1/knowledge/articles/{$this->returns->id}/unpublish")->assertOk();

    $ids = collect($this->postJson('/api/v1/knowledge/search', ['query' => 'devolver etiqueta rastreio prazo', 'top_k' => 10])->json('data'))
        ->pluck('article_id')->unique()->values()->all();

    expect($ids)->toBe([$this->delivery->id]);
});

it('keeps staff-only articles out of reply drafting', function (): void {
    $internal = Knowledge::publish($this->tenant, $this->agent, 'Procedimento interno de estorno', "## Estorno\nO estorno de devolução é aprovado pelo financeiro.", ArticleVisibility::Internal);

    $this->actingAsUser($this->agent);
    $all = collect($this->postJson('/api/v1/knowledge/search', ['query' => 'estorno devolução', 'top_k' => 10])->json('data'))->pluck('article_id');
    $visible = collect($this->postJson('/api/v1/knowledge/search', ['query' => 'estorno devolução', 'top_k' => 10, 'customer_visible_only' => true])->json('data'))->pluck('article_id');

    expect($all)->toContain($internal->id)->and($visible)->not->toContain($internal->id);
});

it('ignores passages embedded with another model', function (): void {
    $this->inTenant($this->tenant, fn () => KbChunk::query()->update(['embedding_model' => 'gemini/gemini-embedding-001']));

    $this->actingAsUser($this->agent)->postJson('/api/v1/knowledge/search', ['query' => 'rastreio'])->assertOk()->assertJsonCount(0, 'data');
});

it('validates the search', function (array $payload, string $field): void {
    $this->actingAsUser($this->agent)->postJson('/api/v1/knowledge/search', $payload)->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([
    'missing query' => [[], 'query'],
    'weight above 1' => [['query' => 'prazo', 'text_weight' => 2], 'text_weight'],
    'too many results' => [['query' => 'prazo', 'top_k' => 50], 'top_k'],
]);

it('refuses to search without a tenant', function (): void {
    expect(fn () => app(KnowledgeSearch::class)->search('prazo', new SearchOptions))->toThrow(MissingTenantException::class);
});

it('returns nothing for empty queries and unknown words', function (): void {
    $this->inTenant($this->tenant, function (): void {
        expect(app(KnowledgeSearch::class)->search('   '))->toBe([]);
        $hits = app(KnowledgeSearch::class)->search('xyzzy quux');
        expect(collect($hits)->max('textScore') ?? 0.0)->toBe(0.0);
    });
});
