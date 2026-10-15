<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\ArticleStatus;
use App\Enums\ArticleVisibility;
use App\Enums\IndexStatus;
use App\Enums\Role;
use App\Models\KbArticle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Knuckles\Scribe\Attributes\ResponseField;

/**
 * @mixin KbArticle
 */
final class KbArticleResource extends JsonResource
{
    /** Include the Markdown body (single-article responses). */
    public bool $withBody = false;

    public function withBody(): self
    {
        $this->withBody = true;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    #[ResponseField('status', 'string', enum: ArticleStatus::class)]
    #[ResponseField('visibility', 'string', enum: ArticleVisibility::class)]
    #[ResponseField('index.status', 'string', enum: IndexStatus::class)]
    #[ResponseField('index.error', 'string', 'Provider error code when indexing failed.', nullable: true)]
    #[ResponseField('index.indexed_at', 'string', nullable: true)]
    #[ResponseField('category', 'object', nullable: true)]
    #[ResponseField('category.description', 'string', nullable: true)]
    #[ResponseField('category.tickets_count', 'integer', required: false)]
    #[ResponseField('body', 'string', 'Markdown working copy (single-article responses only).', required: false)]
    #[ResponseField('published_at', 'string', nullable: true)]
    #[ResponseField('updated_by.email', 'string', 'Only shown to staff.', nullable: true)]
    #[ResponseField('updated_by.role', 'string', enum: Role::class)]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->when($this->withBody, $this->body),
            'visibility' => $this->visibility->value,
            'tags' => $this->tags,
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'status' => $this->status->value,
            'has_unpublished_changes' => $this->hasUnpublishedChanges(),
            'published_at' => $this->published_at?->toIso8601String(),
            'index' => [
                'status' => $this->index_status->value,
                'progress' => $this->index_progress,
                'error' => $this->index_error,
                'indexed_at' => $this->indexed_at?->toIso8601String(),
                'chunks_count' => $this->chunks_count,
            ],
            'citations_count' => $this->citations_count,
            'updated_by' => UserSummaryResource::make($this->whenLoaded('updatedBy')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
