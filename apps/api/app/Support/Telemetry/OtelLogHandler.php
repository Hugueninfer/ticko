<?php

declare(strict_types=1);

namespace App\Support\Telemetry;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord as MonologRecord;
use OpenTelemetry\API\Logs\LogRecord;
use OpenTelemetry\API\Logs\Severity;

/**
 * Sends application logs to the Collector (and Loki), with the trace and
 * span ids of the current request or job. Runs after the redaction
 * processor; only scalar context values are kept, as attributes.
 */
final class OtelLogHandler extends AbstractProcessingHandler
{
    public function __construct(private readonly Telemetry $telemetry, Level $level = Level::Info)
    {
        parent::__construct($level);
    }

    protected function write(MonologRecord $record): void
    {
        $attributes = ['log.channel' => $record->channel];

        foreach ([...$record->extra, ...$record->context] as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $attributes["ticko.{$key}"] = $value;
            } elseif (is_array($value) && isset($value['type']) && is_string($value['type'])) {
                $attributes["ticko.{$key}.type"] = $value['type'];
            }
        }

        $this->telemetry->logger()->emit(
            (new LogRecord($record->message))
                ->setTimestamp((int) $record->datetime->format('Uu') * 1000)
                ->setSeverityNumber(Severity::fromPsr3(strtolower($record->level->getName())))
                ->setSeverityText($record->level->getName())
                ->setAttributes($attributes),
        );
    }
}
