<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Category;
use App\Support\Sla\SlaTarget;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Category
 */
final class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            // Effective targets for every priority; is_custom marks category overrides.
            'sla_targets' => collect($this->slaTargets())->map(fn (SlaTarget $target, string $priority): array => [
                ...$target->toArray(),
                'is_custom' => isset($this->sla_targets[$priority]),
            ])->all(),
            'tickets_count' => $this->whenCounted('tickets'),
        ];
    }
}
