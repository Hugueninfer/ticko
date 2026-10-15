<?php

declare(strict_types=1);

namespace App\Support\Sla;

use App\Enums\TicketPriority;
use App\Models\Category;

/**
 * Time allowed for the first staff reply and for resolution, in minutes.
 * Category targets override the tenant-wide defaults in config/ticko.php.
 * Deadlines run on calendar time (business hours are not modelled yet).
 */
final readonly class SlaTarget
{
    public function __construct(
        public int $firstResponseMinutes,
        public int $resolutionMinutes,
    ) {}

    public static function for(TicketPriority $priority, ?Category $category = null): self
    {
        return $category?->slaTargets()[$priority->value] ?? self::default($priority);
    }

    public static function default(TicketPriority $priority): self
    {
        return new self(
            config()->integer("ticko.sla.{$priority->value}.first_response_minutes"),
            config()->integer("ticko.sla.{$priority->value}.resolution_minutes"),
        );
    }

    /**
     * @return array{first_response_minutes: int, resolution_minutes: int}
     */
    public function toArray(): array
    {
        return [
            'first_response_minutes' => $this->firstResponseMinutes,
            'resolution_minutes' => $this->resolutionMinutes,
        ];
    }
}
