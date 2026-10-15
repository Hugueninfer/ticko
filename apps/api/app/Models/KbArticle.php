<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ArticleStatus;
use App\Enums\ArticleVisibility;
use App\Enums\IndexStatus;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\KbArticleFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A knowledge-base article. `title`/`body` are the working copy; the
 * `published_*` snapshot is what customers may be told and what is indexed.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string|null $category_id
 * @property string $author_id
 * @property string $updated_by_id
 * @property string $title
 * @property string $body
 * @property ArticleVisibility $visibility
 * @property list<string> $tags
 * @property ArticleStatus $status
 * @property string|null $published_title
 * @property string|null $published_body
 * @property Carbon|null $published_at
 * @property IndexStatus $index_status
 * @property int $index_progress
 * @property string|null $index_error
 * @property Carbon|null $indexed_at
 * @property int $chunks_count
 * @property int $citations_count
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Category|null $category
 * @property-read User $author
 * @property-read User $updatedBy
 */
class KbArticle extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<KbArticleFactory> */
    use HasFactory;

    use HasUlids;

    /** @var list<string> */
    protected $fillable = ['title', 'body', 'visibility', 'tags', 'category_id'];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'draft',
        'index_status' => 'none',
        'visibility' => 'customers',
        'tags' => '[]',
    ];

    /** The working copy differs from what is published (and indexed). */
    public function hasUnpublishedChanges(): bool
    {
        return $this->status === ArticleStatus::Published
            && ($this->title !== $this->published_title || $this->body !== $this->published_body);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    /**
     * @return HasMany<KbChunk, $this>
     */
    public function chunks(): HasMany
    {
        return $this->hasMany(KbChunk::class, 'article_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visibility' => ArticleVisibility::class,
            'status' => ArticleStatus::class,
            'index_status' => IndexStatus::class,
            'tags' => 'array',
            'published_at' => 'datetime',
            'indexed_at' => 'datetime',
        ];
    }
}
