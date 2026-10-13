<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Reports\ComputeMetrics;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\MetricsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\ResponseField;

#[Group('Metrics', 'Dashboard figures for admins, always limited to the current tenant.')]
final class MetricsController extends Controller
{
    /**
     * Dashboard metrics
     *
     * Ticket volume, median time to first staff reply and first-response SLA
     * compliance for the period, each compared with the preceding period of
     * the same length.
     */
    #[ScribeResponse([
        'data' => [
            'period' => ['from' => '2026-09-13', 'to' => '2026-10-12'],
            'tickets_received' => ['value' => 1412, 'previous' => 1307],
            'first_response_median_minutes' => ['value' => 14.5, 'previous' => 20.5],
            'sla_first_response_percent' => ['value' => 93.4, 'previous' => 91.3],
            'volume_by_day' => [['date' => '2026-10-12', 'count' => 42]],
            'by_category' => [['category_id' => '01JABCDEF0123456789ABCDEFG', 'name' => 'Hardware', 'count' => 486]],
        ],
    ])]
    #[ResponseField('data.first_response_median_minutes.value', 'number', 'Null when no ticket was answered.', nullable: true)]
    #[ResponseField('data.first_response_median_minutes.previous', 'number', nullable: true)]
    #[ResponseField('data.sla_first_response_percent.value', 'number', 'Null when no deadline is settled yet.', nullable: true)]
    #[ResponseField('data.sla_first_response_percent.previous', 'number', nullable: true)]
    #[ResponseField('data.by_category.category_id', 'string', 'Null for tickets without a category.', nullable: true)]
    #[ResponseField('data.by_category.name', 'string', nullable: true)]
    public function show(MetricsRequest $request, ComputeMetrics $metrics): JsonResponse
    {
        Gate::authorize('viewMetrics');

        return response()->json(['data' => $metrics->handle($request->from(), $request->to())]);
    }
}
