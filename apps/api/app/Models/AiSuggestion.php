<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DiscardReason;
use App\Enums\SuggestionStatus;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\AiSuggestionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A reply drafted by the AI for an agent. It is never sent by itself: an
 * agent approves it (possibly edited) or discards it.
 *
 * @phpstan-type Citation array{n: int, chunk_id: string, article_id: string, title: string, heading: string, quote: string, score: float}
 * @phpstan-type Candidate array{article_id: string, title: string, score: float}
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $ticket_id
 * @property string $requested_by_id
 * @property SuggestionStatus $status
 * @property string|null $body
 * @property string|null $confidence
 * @property list<Citation> $citations
 * @property list<Candidate> $candidates
 * @property string|null $provider
 * @property string|null $model
 * @property string|null $error_code
 * @property int|null $latency_ms
 * @property int|null $input_tokens
 * @property int|null $output_tokens
 * @property string|null $cost_usd Estimated, in US dollars
 * @property string|null $reviewed_by_id
 * @property Carbon|null $reviewed_at
 * @property string|null $message_id
 * @property bool $edited
 * @property DiscardReason|null $discard_reason
 * @property Carbon|null $gap_reported_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Ticket $ticket
 * @property-read User|null $reviewedBy
 */
class AiSuggestion extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<AiSuggestionFactory> */
    use HasFactory;

    use HasUlids;

    /** @var list<string> */
    protected $fillable = ['status', 'body', 'confidence', 'citations', 'candidates', 'provider', 'model', 'error_code', 'latency_ms'];

    /** @var array<string, mixed> */
    protected $attributes = ['status' => 'pending', 'citations' => '[]', 'candidates' => '[]', 'edited' => false];

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SuggestionStatus::class,
            'discard_reason' => DiscardReason::class,
            'citations' => 'array',
            'candidates' => 'array',
            'edited' => 'boolean',
            'reviewed_at' => 'datetime',
            'gap_reported_at' => 'datetime',
        ];
    }
}
