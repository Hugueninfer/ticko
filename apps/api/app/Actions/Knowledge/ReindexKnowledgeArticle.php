<?php

declare(strict_types=1);

namespace App\Actions\Knowledge;

use App\Enums\IndexStatus;
use App\Events\KnowledgeArticleChanged;
use App\Jobs\IndexKnowledgeArticle;
use App\Models\KbArticle;

/** Indexes the published version again (after a failure, or a new embedding model). */
final readonly class ReindexKnowledgeArticle
{
    public function handle(KbArticle $article): KbArticle
    {
        $article->forceFill(['index_status' => IndexStatus::Queued, 'index_progress' => 0, 'index_error' => null])->save();

        IndexKnowledgeArticle::dispatch($article->tenant_id, $article->id)->afterCommit();
        KnowledgeArticleChanged::dispatch($article->tenant_id, $article->id);

        return $article;
    }
}
