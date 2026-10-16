<?php

declare(strict_types=1);

namespace App\Support\Telemetry;

/**
 * Typed view of a propagation carrier (traceparent, tracestate).
 */
final class Carrier
{
    /**
     * @return array<string, string>
     */
    public static function headers(mixed $carrier): array
    {
        $headers = [];

        foreach (is_array($carrier) ? $carrier : [] as $name => $value) {
            if (is_string($name) && is_string($value)) {
                $headers[$name] = $value;
            }
        }

        return $headers;
    }
}
