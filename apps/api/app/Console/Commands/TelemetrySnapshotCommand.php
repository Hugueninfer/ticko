<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Support\Telemetry\Metrics;
use App\Support\Telemetry\Telemetry;
use App\Support\Tenancy\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\WaitTimeCalculator;
use Throwable;

/**
 * State that no single request sees, sampled every minute by the scheduler
 * (`make up-obs`): open, unassigned and overdue tickets per tenant (SLA),
 * queue backlog, wait time and recent failures.
 */
final class TelemetrySnapshotCommand extends Command
{
    protected $signature = 'ticko:telemetry:snapshot';

    protected $description = 'Record SLA and queue gauges for the dashboards (no-op unless telemetry is on)';

    public function handle(Telemetry $telemetry, Metrics $metrics, TenantContext $tenants): int
    {
        if (! $telemetry->enabled()) {
            return self::SUCCESS;
        }

        foreach (Tenant::query()->orderBy('slug')->get() as $tenant) {
            $tenants->run($tenant, fn () => $this->tickets($metrics, $tenant->slug));
        }

        $this->queues($metrics);
        $telemetry->flush();

        return self::SUCCESS;
    }

    private function tickets(Metrics $metrics, string $tenant): void
    {
        $active = [TicketStatus::Open->value, TicketStatus::Pending->value];
        $open = Ticket::query()->whereIn('status', $active)->toBase()
            ->selectRaw('priority, count(*) as total')->groupBy('priority')->pluck('total', 'priority');

        foreach (TicketPriority::cases() as $priority) {
            $count = $open[$priority->value] ?? 0;
            $metrics->gauge('ticko.tickets.open', 'Open and pending tickets', is_numeric($count) ? (int) $count : 0, ['tenant' => $tenant, 'priority' => $priority->value]);
        }

        $now = now();
        $firstResponse = Ticket::query()->whereIn('status', $active)->whereNull('first_response_at')->where('first_response_due_at', '<', $now)->count();
        $resolution = Ticket::query()->whereIn('status', $active)->where('resolution_due_at', '<', $now)->count();

        $metrics->gauge('ticko.tickets.overdue', 'Tickets past an SLA deadline', $firstResponse, ['tenant' => $tenant, 'deadline' => 'first_response']);
        $metrics->gauge('ticko.tickets.overdue', 'Tickets past an SLA deadline', $resolution, ['tenant' => $tenant, 'deadline' => 'resolution']);
        $metrics->gauge('ticko.tickets.unassigned', 'Open tickets without an assignee', Ticket::query()->where('status', TicketStatus::Open->value)->whereNull('assignee_id')->count(), ['tenant' => $tenant]);
    }

    private function queues(Metrics $metrics): void
    {
        try {
            foreach (config()->array('horizon.defaults.supervisor-1.queue') as $queue) {
                if (is_string($queue)) {
                    $metrics->gauge('ticko.queue.pending', 'Jobs waiting in the queue', Queue::connection('redis')->size($queue), ['queue' => $queue]);
                }
            }

            /** @var array<string, int|float> $waits */
            $waits = app(WaitTimeCalculator::class)->calculate();

            foreach ($waits as $queue => $seconds) {
                $metrics->gauge('ticko.queue.wait', 'Estimated wait for a new job (Horizon), in seconds', $seconds, ['queue' => $queue]);
            }

            $metrics->gauge('ticko.queue.failed_recent', 'Jobs failed in the last 7 days (Horizon)', app(JobRepository::class)->countRecentlyFailed(), []);
        } catch (Throwable) {
            // Redis unavailable: the queue panels show a gap.
        }
    }
}
