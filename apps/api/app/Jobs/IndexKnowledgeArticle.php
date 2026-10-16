<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ArticleStatus;
use App\Enums\IndexStatus;
use App\Events\KnowledgeArticleChanged;
use App\Models\KbArticle;
use App\Models\KbChunk;
use App\Models\Tenant;
use App\Services\Ai\AiUnavailable;
use App\Services\Ai\Embedder;
use App\Services\Ai\EmbeddingTask;
use App\Services\Knowledge\MarkdownChunker;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Splits the published version of an article into passages, embeds them
 * and swaps them in atomically, reporting progress to the knowledge-base
 * screen. A provider failure marks the article "failed" (retry from the UI);
 * the previous passages stay searchable until the swap.
 */
final class IndexKnowledgeArticle implements ShouldQueue
{
    use Queueable;

    private const int BATCH = 16;

    public int $tries = 1;

    public function __construct(
        public readonly string $tenantId,
        public readonly string $articleId,
    ) {}

    public function handle(TenantContext $tenants, MarkdownChunker $chunker, Embedder $embedder): void
    {
        $tenant = Tenant::find($this->tenantId);

        if ($tenant !== null) {
            $tenants->run($tenant, fn () => $this->index($chunker, $embedder));
        }
    }

    private function index(MarkdownChunker $chunker, Embedder $embedder): void
    {
        $article = KbArticle::query()->find($this->articleId);

        if ($article === null) {
            return;
        }

        if ($article->status !== ArticleStatus::Published || $article->published_body === null) {
            $this->clear($article);

            return;
        }

        $title = (string) $article->published_title;
        $chunks = $chunker->chunk($title, $article->published_body);
        $this->progress($article, IndexStatus::Indexing, 0);

        try {
            $vectors = [];

            foreach (array_chunk($chunks, self::BATCH) as $batch) {
                array_push($vectors, ...$embedder->embed(array_map(fn ($chunk): string => $chunk->embeddingInput($title), $batch), EmbeddingTask::Document));
                $this->progress($article, IndexStatus::Indexing, (int) floor(count($vectors) * 100 / max(1, count($chunks))));
            }
        } catch (AiUnavailable $exception) {
            $article->forceFill(['index_status' => IndexStatus::Failed, 'index_error' => $exception->errorCode])->save();
            KnowledgeArticleChanged::dispatch($article->tenant_id, $article->id);

            return;
        }

        DB::transaction(function () use ($article, $chunks, $vectors, $embedder): void {
            $article->chunks()->delete();

            foreach ($chunks as $i => $chunk) {
                $model = new KbChunk([
                    'ordinal' => $chunk->ordinal,
                    'heading' => $chunk->heading,
                    'content' => $chunk->content,
                    'tokens' => $chunk->tokens,
                    'embedding_model' => $embedder->embeddingModel(),
                    'embedding' => $vectors[$i],
                ]);
                $model->article_id = $article->id;
                $model->save();
            }

            $article->forceFill([
                'index_status' => IndexStatus::Indexed,
                'index_progress' => 100,
                'index_error' => null,
                'indexed_at' => now(),
                'chunks_count' => count($chunks),
            ])->save();
        });

        KnowledgeArticleChanged::dispatch($article->tenant_id, $article->id);
    }

    private function progress(KbArticle $article, IndexStatus $status, int $percent): void
    {
        $article->forceFill(['index_status' => $status, 'index_progress' => $percent, 'index_error' => null])->save();
        KnowledgeArticleChanged::dispatch($article->tenant_id, $article->id);
    }

    private function clear(KbArticle $article): void
    {
        DB::transaction(function () use ($article): void {
            $article->chunks()->delete();
            $article->forceFill(['index_status' => IndexStatus::None, 'index_progress' => 0, 'index_error' => null, 'chunks_count' => 0])->save();
        });

        KnowledgeArticleChanged::dispatch($article->tenant_id, $article->id);
    }
}
