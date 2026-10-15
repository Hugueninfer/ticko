<?php

declare(strict_types=1);

namespace App\Actions\Knowledge;

use App\Events\KnowledgeArticleChanged;
use App\Models\KbArticle;
use App\Models\User;

/**
 * Creates or edits the working copy. A published article keeps serving
 * (and being cited from) its published version until it is republished.
 */
final readonly class SaveKnowledgeArticle
{
    /**
     * @param  array{title?: string, body?: string, visibility?: string, tags?: list<string>, category_id?: string|null}  $data
     */
    public function handle(User $editor, array $data, ?KbArticle $article = null): KbArticle
    {
        $article ??= new KbArticle;
        $article->fill($data);

        if (! $article->exists) {
            $article->author_id = $editor->id;
        }

        $article->updated_by_id = $editor->id;
        $article->save();

        KnowledgeArticleChanged::dispatch($article->tenant_id, $article->id);

        return $article;
    }
}
