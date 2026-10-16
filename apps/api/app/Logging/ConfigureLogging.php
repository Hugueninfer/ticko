<?php

declare(strict_types=1);

namespace App\Logging;

use App\Support\Telemetry\OtelLogHandler;
use App\Support\Telemetry\Telemetry;
use Illuminate\Log\Logger;
use Monolog\Logger as Monolog;

/**
 * Logging "tap" for every channel: redact first, then (with telemetry on)
 * also send the record to the OpenTelemetry Collector.
 */
final readonly class ConfigureLogging
{
    public function __construct(private Telemetry $telemetry) {}

    public function __invoke(Logger $logger): void
    {
        $monolog = $logger->getLogger();

        if (! $monolog instanceof Monolog) {
            return;
        }

        if ($this->telemetry->enabled()) {
            $monolog->pushHandler(new OtelLogHandler($this->telemetry));
        }

        // Processors run before every handler, so the redaction covers both.
        $monolog->pushProcessor(new RedactSensitiveData);
    }
}
