<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Concerns\BelongsToTenant;
use App\Support\Sla\SlaTarget;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $requester_id
 * @property string|null $assignee_id
 * @property string|null $category_id
 * @property string $subject
 * @property string $description
 * @property TicketStatus $status
 * @property TicketPriority $priority
 * @property Carbon|null $resolved_at
 * @property Carbon|null $closed_at
 * @property Carbon|null $first_response_at
 * @property Carbon|null $first_response_due_at
 * @property Carbon|null $resolution_due_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $requester
 * @property-read User|null $assignee
 * @property-read Category|null $category
 */
class Ticket extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    use HasUlids;

    /** @var list<string> */
    protected $fillable = ['subject', 'description', 'priority', 'category_id'];

    protected static function booted(): void
    {
        // Deadlines are fixed when the ticket is opened, from its category and priority.
        static::creating(function (Ticket $ticket): void {
            $opened = $ticket->created_at ?? now();
            $priority = $ticket->priority instanceof TicketPriority ? $ticket->priority : TicketPriority::from((string) $ticket->priority);
            $target = SlaTarget::for($priority, $ticket->category_id !== null ? Category::find($ticket->category_id) : null);

            $ticket->first_response_due_at ??= $opened->copy()->addMinutes($target->firstResponseMinutes);
            $ticket->resolution_due_at ??= $opened->copy()->addMinutes($target->resolutionMinutes);
        });
    }

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'open',
        'priority' => 'normal',
    ];

    /**
     * Short reference shown to people ("#4F2K9Z"): the last 6 characters of the ULID.
     */
    public function reference(): string
    {
        return '#'.mb_strtoupper(mb_substr($this->id, -6));
    }

    /**
     * Whether a pending SLA deadline has already passed.
     */
    public function isOverdue(): bool
    {
        if (in_array($this->status, [TicketStatus::Resolved, TicketStatus::Closed], true)) {
            return false;
        }

        return ($this->first_response_at === null && $this->first_response_due_at?->isPast() === true)
            || $this->resolution_due_at?->isPast() === true;
    }

    /**
     * Unresolved tickets with a missed first-response or resolution deadline.
     *
     * @param  Builder<Ticket>  $query
     */
    public function scopeOverdue(Builder $query): void
    {
        $now = now();

        $query->whereIn('status', [TicketStatus::Open->value, TicketStatus::Pending->value])
            ->where(fn (Builder $q) => $q
                ->where(fn (Builder $q) => $q->whereNull('first_response_at')->where('first_response_due_at', '<', $now))
                ->orWhere('resolution_due_at', '<', $now));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<TicketMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    /**
     * @return HasMany<Attachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'first_response_at' => 'datetime',
            'first_response_due_at' => 'datetime',
            'resolution_due_at' => 'datetime',
        ];
    }
}
