<?php

declare(strict_types=1);

namespace App\Logging;

use App\Support\Telemetry\Redactor;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Monolog processor on every log channel: secrets, customer content and
 * personal data never reach a log line (see Redactor).
 */
final class RedactSensitiveData implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            message: Redactor::string($record->message),
            context: Redactor::array($record->context),
            extra: Redactor::array($record->extra),
        );
    }
}
