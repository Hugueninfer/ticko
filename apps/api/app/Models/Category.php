<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TicketPriority;
use App\Models\Concerns\BelongsToTenant;
use App\Support\Sla\SlaTarget;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property array<string, array{first_response_minutes: int, resolution_minutes: int}>|null $sla_targets
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Category extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    use HasUlids;

    /** @var list<string> */
    protected $fillable = ['name', 'slug', 'description', 'sla_targets'];

    /**
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Effective SLA target for every priority (category override or tenant default).
     *
     * @return array<string, SlaTarget>
     */
    public function slaTargets(): array
    {
        $targets = [];

        foreach (TicketPriority::cases() as $priority) {
            $custom = $this->sla_targets[$priority->value] ?? null;
            $targets[$priority->value] = $custom !== null
                ? new SlaTarget($custom['first_response_minutes'], $custom['resolution_minutes'])
                : SlaTarget::default($priority);
        }

        return $targets;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['sla_targets' => 'array'];
    }
}
