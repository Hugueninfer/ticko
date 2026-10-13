<?php

declare(strict_types=1);

namespace App\Actions\Reports;

use App\Models\Category;
use App\Models\Ticket;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Admin dashboard figures for one period, compared with the period of the same
 * length right before it. Every query goes through the tenant scope (and RLS).
 * Days are calendar days in the application timezone.
 */
final readonly class ComputeMetrics
{
    /**
     * @return array{
     *     period: array{from: string, to: string},
     *     tickets_received: array{value: int, previous: int},
     *     first_response_median_minutes: array{value: float|null, previous: float|null},
     *     sla_first_response_percent: array{value: float|null, previous: float|null},
     *     volume_by_day: list<array{date: string, count: int}>,
     *     by_category: list<array{category_id: string|null, name: string|null, count: int}>
     * }
     */
    public function handle(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $start = $from->startOfDay();
        $end = $to->endOfDay();
        $days = (int) $start->diffInDays($end->addSecond());
        $previousStart = $start->subDays($days);
        $previousEnd = $start->subSecond();

        return [
            'period' => ['from' => $start->toDateString(), 'to' => $end->toDateString()],
            'tickets_received' => [
                'value' => $this->created($start, $end)->count(),
                'previous' => $this->created($previousStart, $previousEnd)->count(),
            ],
            'first_response_median_minutes' => [
                'value' => $this->medianFirstResponse($start, $end),
                'previous' => $this->medianFirstResponse($previousStart, $previousEnd),
            ],
            'sla_first_response_percent' => [
                'value' => $this->slaPercent($start, $end),
                'previous' => $this->slaPercent($previousStart, $previousEnd),
            ],
            'volume_by_day' => $this->volumeByDay($start, $end),
            'by_category' => $this->byCategory($start, $end),
        ];
    }

    /**
     * @return Builder<Ticket>
     */
    private function created(CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        return Ticket::query()->whereBetween('created_at', [$start, $end]);
    }

    private function medianFirstResponse(CarbonImmutable $start, CarbonImmutable $end): ?float
    {
        $seconds = $this->created($start, $end)
            ->whereNotNull('first_response_at')
            ->toBase()
            ->selectRaw('percentile_cont(0.5) within group (order by extract(epoch from (first_response_at - created_at))) as median')
            ->value('median');

        return is_numeric($seconds) ? round((float) $seconds / 60, 1) : null;
    }

    /**
     * Share of tickets whose first-response deadline is settled (answered, or
     * already past) that were answered in time.
     */
    private function slaPercent(CarbonImmutable $start, CarbonImmutable $end): ?float
    {
        $row = $this->created($start, $end)
            ->whereNotNull('first_response_due_at')
            ->where(fn (Builder $q) => $q->whereNotNull('first_response_at')->orWhere('first_response_due_at', '<', now()))
            ->toBase()
            ->selectRaw('count(*) as total, count(*) filter (where first_response_at <= first_response_due_at) as met')
            ->first();

        $total = is_object($row) && is_numeric($row->total ?? null) ? (int) $row->total : 0;
        $met = is_object($row) && is_numeric($row->met ?? null) ? (int) $row->met : 0;

        return $total > 0 ? round($met * 100 / $total, 1) : null;
    }

    /**
     * @return list<array{date: string, count: int}>
     */
    private function volumeByDay(CarbonImmutable $start, CarbonImmutable $end): array
    {
        /** @var array<string, int> $counts */
        $counts = $this->created($start, $end)
            ->toBase()
            ->selectRaw('cast(created_at as date) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day')
            ->map(fn (mixed $total): int => is_numeric($total) ? (int) $total : 0)
            ->all();

        $volume = [];

        for ($day = $start; $day->lte($end); $day = $day->addDay()) {
            $date = $day->toDateString();
            $volume[] = ['date' => $date, 'count' => $counts[$date] ?? 0];
        }

        return $volume;
    }

    /**
     * @return list<array{category_id: string|null, name: string|null, count: int}>
     */
    private function byCategory(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $rows = $this->created($start, $end)
            ->toBase()
            ->selectRaw('category_id, count(*) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get();

        $names = Category::query()
            ->whereIn('id', $rows->pluck('category_id')->filter()->all())
            ->pluck('name', 'id');

        return array_values($rows->map(function (object $row) use ($names): array {
            $id = is_string($row->category_id ?? null) ? $row->category_id : null;
            $name = $id !== null ? $names->get($id) : null;

            return [
                'category_id' => $id,
                'name' => is_string($name) ? $name : null,
                'count' => is_numeric($row->total ?? null) ? (int) $row->total : 0,
            ];
        })->all());
    }
}
