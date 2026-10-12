<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\TicketMessageFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $ticket_id
 * @property string $author_id
 * @property string $body
 * @property bool $is_internal
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Ticket $ticket
 * @property-read User $author
 */
class TicketMessage extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<TicketMessageFactory> */
    use HasFactory;

    use HasUlids;

    /** @var list<string> */
    protected $fillable = ['body', 'is_internal'];

    /** @var array<string, mixed> */
    protected $attributes = ['is_internal' => false];

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
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_internal' => 'boolean'];
    }
}
