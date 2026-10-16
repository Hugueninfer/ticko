<?php

declare(strict_types=1);

namespace App\Actions\Knowledge;

use App\Enums\ArticleStatus;
use App\Enums\IndexStatus;
use App\Events\KnowledgeArticleChanged;
use App\Models\KbArticle;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Takes an article out of the AI's reach at once: its passages are deleted in the same transaction. */
final readonly class UnpublishKnowledgeArticle
{
    public function handle(User $editor, KbArticle $article): KbArticle
    {
        DB::transaction(function () use ($editor, $article): void {
            $article->chunks()->delete();
            $article->forceFill([
                'status' => ArticleStatus::Archived,
                'updated_by_id' => $editor->id,
                'index_status' => IndexStatus::None,
                'index_progress' => 0,
                'index_error' => null,
                'chunks_count' => 0,
            ])->save();
        });

        KnowledgeArticleChanged::dispatch($article->tenant_id, $article->id);

        return $article;
    }
}
