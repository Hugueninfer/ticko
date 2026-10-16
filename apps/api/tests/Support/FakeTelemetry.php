<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Support\Telemetry\Metrics;
use App\Support\Telemetry\Telemetry;
use App\Support\Telemetry\TraceJobs;
use App\Support\Telemetry\TraceRequests;
use ArrayObject;
use OpenTelemetry\SDK\Logs\Exporter\InMemoryExporter as LogExporter;
use OpenTelemetry\SDK\Logs\ReadableLogRecord;
use OpenTelemetry\SDK\Metrics\Data\HistogramDataPoint;
use OpenTelemetry\SDK\Metrics\Data\Metric;
use OpenTelemetry\SDK\Metrics\Data\NumberDataPoint;
use OpenTelemetry\SDK\Metrics\Data\Temporality;
use OpenTelemetry\SDK\Metrics\MetricExporter\InMemoryExporter as MetricExporter;
use OpenTelemetry\SDK\Trace\ImmutableSpan;
use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter as SpanExporter;
use RuntimeException;

/**
 * Telemetry with in-memory exporters: what would reach the Collector.
 */
final class FakeTelemetry
{
    public readonly Telemetry $telemetry;

    /** @var ArrayObject<int, ImmutableSpan> */
    private ArrayObject $spans;

    /** @var ArrayObject<int, ReadableLogRecord> */
    private ArrayObject $logs;

    private MetricExporter $metrics;

    /** @var list<array{name: string, value: int|float, attributes: array<string, mixed>}> */
    private array $points = [];

    private function __construct()
    {
        $this->spans = new ArrayObject;
        $this->logs = new ArrayObject;
        $this->metrics = new MetricExporter(new ArrayObject, Temporality::DELTA);
        $this->telemetry = Telemetry::withExporters(
            new SpanExporter($this->spans),
            $this->metrics,
            new LogExporter($this->logs),
            ['service.name' => 'ticko-test'],
        );
    }

    public static function install(): self
    {
        $fake = new self;
        app()->instance(Telemetry::class, $fake->telemetry);

        foreach ([Metrics::class, TraceRequests::class, TraceJobs::class] as $dependent) {
            app()->forgetInstance($dependent);
        }

        // Log channels are built once; rebuild them with this instance.
        app('log')->forgetChannel();

        return $fake;
    }

    /** @return list<ImmutableSpan> */
    public function spans(): array
    {
        return array_values($this->spans->getArrayCopy());
    }

    public function span(string $name): ImmutableSpan
    {
        foreach ($this->spans() as $span) {
            if ($span->getName() === $name) {
                return $span;
            }
        }

        throw new RuntimeException("No span named {$name}; got: ".implode(', ', array_map(fn ($s) => $s->getName(), $this->spans())));
    }

    /** @return list<ReadableLogRecord> */
    public function logs(): array
    {
        $this->telemetry->flush();

        return array_values($this->logs->getArrayCopy());
    }

    /**
     * Every data point exported so far (sums and gauges: value; histograms: count).
     *
     * @return list<array{name: string, value: int|float, attributes: array<string, mixed>}>
     */
    public function points(): array
    {
        $this->telemetry->flush();

        foreach ($this->metrics->collect(true) as $metric) {
            /** @var Metric $metric */
            foreach ($metric->data->dataPoints ?? [] as $point) {
                $this->points[] = [
                    'name' => $metric->name,
                    'value' => $point instanceof HistogramDataPoint ? $point->count : ($point instanceof NumberDataPoint ? $point->value : 0),
                    'attributes' => $point->attributes->toArray(),
                ];
            }
        }

        return $this->points;
    }

    /**
     * Sum of a metric's points whose attributes include $where.
     *
     * @param  array<string, mixed>  $where
     */
    public function total(string $name, array $where = []): int|float
    {
        $total = 0;

        foreach ($this->points() as $point) {
            if ($point['name'] === $name && array_intersect_assoc($where, $point['attributes']) === $where) {
                $total += $point['value'];
            }
        }

        return $total;
    }

    /** Everything exported, as one string, to look for leaks. */
    public function dump(): string
    {
        $parts = [];

        foreach ($this->spans() as $span) {
            $parts[] = $span->getName().' '.json_encode($span->getAttributes()->toArray());

            foreach ($span->getEvents() as $event) {
                $parts[] = $event->getName().' '.json_encode($event->getAttributes()->toArray());
            }
        }

        foreach ($this->logs() as $log) {
            $parts[] = json_encode($log->getBody()).' '.json_encode($log->getAttributes()->toArray());
        }

        foreach ($this->points() as $point) {
            $parts[] = $point['name'].' '.json_encode($point['attributes']);
        }

        return implode("\n", $parts);
    }
}
