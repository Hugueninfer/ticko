<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ClassificationStatus;
use App\Enums\Sentiment;
use App\Enums\TicketPriority;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\TicketClassificationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One answer of the classifier service for a ticket (or its failure).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $ticket_id
 * @property ClassificationStatus $status
 * @property string|null $model
 * @property string|null $category_label
 * @property string|null $category_confidence
 * @property TicketPriority|null $priority
 * @property string|null $priority_confidence
 * @property Sentiment|null $sentiment
 * @property string|null $sentiment_confidence
 * @property list<string> $applied Fields written to the ticket
 * @property list<string> $flags
 * @property string|null $error_code
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Ticket $ticket
 */
class TicketClassification extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<TicketClassificationFactory> */
    use HasFactory;

    use HasUlids;

    /** @var list<string> */
    protected $fillable = [
        'status', 'model', 'category_label', 'category_confidence', 'priority', 'priority_confidence',
        'sentiment', 'sentiment_confidence', 'applied', 'flags', 'error_code',
    ];

    /** @var array<string, mixed> */
    protected $attributes = ['applied' => '[]', 'flags' => '[]'];

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ClassificationStatus::class,
            'priority' => TicketPriority::class,
            'sentiment' => Sentiment::class,
            'applied' => 'array',
            'flags' => 'array',
        ];
    }
}
