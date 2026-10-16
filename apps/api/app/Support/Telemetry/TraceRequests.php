<?php

declare(strict_types=1);

namespace App\Support\Telemetry;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;

/**
 * One server span and one latency sample per API request.
 *
 * Only the route template is recorded (`api/v1/tickets/{ticket}`), never the
 * URL, its query string (signed URLs carry signatures), headers or bodies.
 * Incoming `traceparent` headers are ignored: browsers and phones do not
 * get to choose trace ids. Telemetry is flushed after the response is sent.
 */
final readonly class TraceRequests
{
    public function __construct(private Telemetry $telemetry, private Metrics $metrics, private TenantContext $tenants) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->telemetry->enabled() || $request->is('up')) {
            return self::response($next($request));
        }

        $method = $request->method() !== '' ? $request->method() : 'HTTP';
        $started = hrtime(true);
        $span = $this->telemetry->tracer()->spanBuilder($method)
            ->setSpanKind(SpanKind::KIND_SERVER)
            ->setParent(false)
            ->setAttribute('http.request.method', $request->method())
            ->startSpan();
        $scope = $span->activate();

        try {
            $response = self::response($next($request));
        } finally {
            $scope->detach();
        }

        $route = self::route($request);
        $status = $response->getStatusCode();
        $span->updateName("{$request->method()} {$route}")
            ->setAttribute('http.route', $route)
            ->setAttribute('http.response.status_code', $status)
            ->setAttribute('ticko.tenant', $this->tenants->tenant()->slug ?? '');

        if ($status >= 500) {
            $span->setStatus(StatusCode::STATUS_ERROR);
        }

        $span->end();
        $this->metrics->httpRequest($request->method(), $route, $status, (hrtime(true) - $started) / 1e9);

        return $response;
    }

    public function terminate(): void
    {
        $this->telemetry->flush();
    }

    private static function response(mixed $response): Response
    {
        return $response instanceof Response ? $response : throw new UnexpectedValueException('Middleware did not return a response.');
    }

    private static function route(Request $request): string
    {
        $route = $request->route();

        return $route === null ? 'unmatched' : '/'.ltrim($route->uri(), '/');
    }
}
