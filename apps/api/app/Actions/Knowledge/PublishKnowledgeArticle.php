<?php

declare(strict_types=1);

namespace App\Actions\Knowledge;

use App\Enums\ArticleStatus;
use App\Enums\IndexStatus;
use App\Events\KnowledgeArticleChanged;
use App\Jobs\IndexKnowledgeArticle;
use App\Models\KbArticle;
use App\Models\User;

/** Snapshots the working copy as the published version and queues its indexing. */
final readonly class PublishKnowledgeArticle
{
    public function handle(User $editor, KbArticle $article): KbArticle
    {
        $article->forceFill([
            'status' => ArticleStatus::Published,
            'published_title' => $article->title,
            'published_body' => $article->body,
            'published_at' => now(),
            'updated_by_id' => $editor->id,
            'index_status' => IndexStatus::Queued,
            'index_progress' => 0,
            'index_error' => null,
        ])->save();

        IndexKnowledgeArticle::dispatch($article->tenant_id, $article->id)->afterCommit();
        KnowledgeArticleChanged::dispatch($article->tenant_id, $article->id);

        return $article;
    }
}
