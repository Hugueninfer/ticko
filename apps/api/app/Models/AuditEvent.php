<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Append-only audit trail (the database refuses updates). `actor_id` is
 * null for automatic actions (e.g. the classifier).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string|null $actor_id
 * @property string $action
 * @property string $subject_type
 * @property string $subject_id
 * @property array<string, mixed> $data
 * @property Carbon $created_at
 */
class AuditEvent extends Model
{
    use BelongsToTenant;
    use HasUlids;

    public const null UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = ['action', 'subject_type', 'subject_id', 'data'];

    /**
     * @param  array<string, mixed>  $data
     */
    public static function record(string $action, Model $subject, ?User $actor, array $data = []): self
    {
        $key = $subject->getKey();
        $event = new self(['action' => $action, 'subject_type' => class_basename($subject), 'subject_id' => is_string($key) ? $key : '', 'data' => $data]);
        $event->actor_id = $actor?->id;
        $event->save();

        return $event;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['data' => 'array'];
    }
}
