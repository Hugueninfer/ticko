<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Casts\AsVector;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One indexed passage of a published article.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $article_id
 * @property int $ordinal
 * @property string $heading
 * @property string $content
 * @property int $tokens
 * @property string $embedding_model
 * @property list<float> $embedding
 * @property int $citations_count
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read KbArticle $article
 */
class KbChunk extends Model
{
    use BelongsToTenant;
    use HasUlids;

    /** @var list<string> */
    protected $fillable = ['ordinal', 'heading', 'content', 'tokens', 'embedding_model', 'embedding'];

    /** @var list<string> */
    protected $hidden = ['embedding'];

    /**
     * @return BelongsTo<KbArticle, $this>
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(KbArticle::class, 'article_id');
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['embedding' => AsVector::class];
    }
}
