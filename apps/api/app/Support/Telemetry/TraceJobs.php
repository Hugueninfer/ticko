<?php

declare(strict_types=1);

namespace App\Support\Telemetry;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobAttempted;
use Illuminate\Queue\Events\JobProcessing;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\ScopeInterface;

/**
 * Queued jobs as consumer spans, continuing the trace of the request that
 * queued them (the `traceparent` travels in the job payload), plus a
 * duration sample per attempt. Telemetry is flushed after every job, since
 * queue workers live for a long time.
 */
final class TraceJobs
{
    /** @var array<string, array{SpanInterface, ScopeInterface, int|float}> */
    private array $running = [];

    public function __construct(private readonly Telemetry $telemetry, private readonly Metrics $metrics) {}

    /**
     * Added to every queued job's payload.
     *
     * @return array{telemetry?: array<string, string>}
     */
    public function payload(): array
    {
        $carrier = [];
        TraceContextPropagator::getInstance()->inject($carrier);
        $headers = Carrier::headers($carrier);

        return $headers === [] ? [] : ['telemetry' => $headers];
    }

    public function starting(JobProcessing $event): void
    {
        $payload = $event->job->payload();
        $carrier = is_array($payload['telemetry'] ?? null) ? $payload['telemetry'] : [];
        $parent = TraceContextPropagator::getInstance()->extract($carrier);

        $span = $this->telemetry->tracer()->spanBuilder('job '.self::name($event->job))
            ->setSpanKind(SpanKind::KIND_CONSUMER)
            ->setParent($parent)
            ->setAttribute('messaging.system', 'redis')
            ->setAttribute('messaging.destination.name', $event->job->getQueue())
            ->setAttribute('ticko.job', self::name($event->job))
            ->setAttribute('ticko.job.attempt', $event->job->attempts())
            ->startSpan();

        $this->running[self::key($event->job)] = [$span, $span->activate(), hrtime(true)];
    }

    public function finished(JobAttempted $event): void
    {
        $key = self::key($event->job);

        if (! isset($this->running[$key])) {
            return;
        }

        [$span, $scope, $started] = $this->running[$key];
        unset($this->running[$key]);

        $outcome = match (true) {
            $event->successful() => $event->job->isReleased() ? 'released' : 'processed',
            $event->job->hasFailed() => 'failed',
            default => 'released',
        };

        if ($event->exception !== null) {
            $span->addEvent('exception', ['exception.type' => $event->exception::class]);
        }

        if ($outcome === 'failed') {
            $span->setStatus(StatusCode::STATUS_ERROR);
        }

        $span->setAttribute('ticko.job.outcome', $outcome);
        $scope->detach();
        $span->end();
        $this->metrics->job(self::name($event->job), $outcome, (hrtime(true) - $started) / 1e9);
        $this->telemetry->flush();
    }

    private static function name(Job $job): string
    {
        return class_basename($job->resolveName());
    }

    private static function key(Job $job): string
    {
        // Job ids can be empty (sync queue) and jobs nest; the object is unique.
        return spl_object_hash($job);
    }
}
