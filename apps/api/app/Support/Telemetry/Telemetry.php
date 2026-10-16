<?php

declare(strict_types=1);

namespace App\Support\Telemetry;

use OpenTelemetry\API\Common\Time\Clock;
use OpenTelemetry\API\Logs\LoggerInterface;
use OpenTelemetry\API\Logs\NoopLogger;
use OpenTelemetry\API\Metrics\MeterInterface;
use OpenTelemetry\API\Metrics\Noop\NoopMeter;
use OpenTelemetry\API\Trace\NoopTracer;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\Contrib\Otlp\LogsExporter;
use OpenTelemetry\Contrib\Otlp\MetricExporter;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Logs\LoggerProvider;
use OpenTelemetry\SDK\Logs\LoggerProviderInterface;
use OpenTelemetry\SDK\Logs\LogRecordExporterInterface;
use OpenTelemetry\SDK\Logs\Processor\BatchLogRecordProcessor;
use OpenTelemetry\SDK\Logs\Processor\SimpleLogRecordProcessor;
use OpenTelemetry\SDK\Metrics\Data\Temporality;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MeterProviderInterface;
use OpenTelemetry\SDK\Metrics\MetricExporterInterface;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\Sampler\ParentBased;
use OpenTelemetry\SDK\Trace\SpanExporterInterface;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\TracerProviderInterface;
use Throwable;

/**
 * Traces, metrics and logs for one PHP process (a request, a job worker, a
 * command), exported over OTLP/HTTP to the OpenTelemetry Collector.
 *
 * PHP-FPM processes are short-lived, so metrics use delta temporality and
 * are flushed when the request or job ends; the Collector turns them into
 * cumulative series for Prometheus. The resource has no per-process
 * attributes (pid, host), so every process reports into the same series.
 *
 * Disabled (the default without `make up-obs`), everything is a no-op.
 */
final class Telemetry
{
    private ?TracerProviderInterface $tracerProvider = null;

    private ?MeterProviderInterface $meterProvider = null;

    private ?LoggerProviderInterface $loggerProvider = null;

    private TracerInterface $tracer;

    private MeterInterface $meter;

    private LoggerInterface $logger;

    private function __construct()
    {
        $this->tracer = new NoopTracer;
        $this->meter = new NoopMeter;
        $this->logger = new NoopLogger;
    }

    public static function disabled(): self
    {
        return new self;
    }

    /**
     * @param  array<string, string>  $resource  e.g. service.name, deployment.environment.name
     */
    public static function otlp(string $endpoint, array $resource, float $timeoutSeconds = 1.0): self
    {
        $factory = new OtlpHttpTransportFactory;
        $transport = fn (string $signal) => $factory->create(
            rtrim($endpoint, '/')."/v1/{$signal}",
            'application/x-protobuf',
            timeout: $timeoutSeconds,
            maxRetries: 1,
        );

        return self::withExporters(
            new SpanExporter($transport('traces')),
            new MetricExporter($transport('metrics'), Temporality::DELTA),
            new LogsExporter($transport('logs')),
            $resource,
            batch: true,
        );
    }

    /**
     * @param  array<string, string>  $resource
     */
    public static function withExporters(
        SpanExporterInterface $spans,
        MetricExporterInterface $metrics,
        LogRecordExporterInterface $logs,
        array $resource,
        bool $batch = false,
    ): self {
        $telemetry = new self;
        $info = ResourceInfo::create(Attributes::create($resource));

        $telemetry->tracerProvider = TracerProvider::builder()
            ->setResource($info)
            ->setSampler(new ParentBased(new AlwaysOnSampler))
            ->addSpanProcessor($batch ? new BatchSpanProcessor($spans, Clock::getDefault()) : new SimpleSpanProcessor($spans))
            ->build();
        $telemetry->meterProvider = MeterProvider::builder()
            ->setResource($info)
            ->addReader(new ExportingReader($metrics))
            ->build();
        $telemetry->loggerProvider = LoggerProvider::builder()
            ->setResource($info)
            ->addLogRecordProcessor($batch ? new BatchLogRecordProcessor($logs, Clock::getDefault()) : new SimpleLogRecordProcessor($logs))
            ->build();

        $telemetry->tracer = $telemetry->tracerProvider->getTracer('ticko');
        $telemetry->meter = $telemetry->meterProvider->getMeter('ticko');
        $telemetry->logger = $telemetry->loggerProvider->getLogger('ticko');

        return $telemetry;
    }

    public function enabled(): bool
    {
        return $this->tracerProvider !== null;
    }

    public function tracer(): TracerInterface
    {
        return $this->tracer;
    }

    public function meter(): MeterInterface
    {
        return $this->meter;
    }

    public function logger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Sends everything recorded so far. Telemetry never breaks the app:
     * an unreachable Collector only loses data.
     */
    public function flush(): void
    {
        try {
            $this->tracerProvider?->forceFlush();
            $this->meterProvider?->forceFlush();
            $this->loggerProvider?->forceFlush();
        } catch (Throwable) {
            // Ignored on purpose.
        }
    }
}
