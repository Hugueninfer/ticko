<?php

declare(strict_types=1);

namespace App\Support\Telemetry;

use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Guzzle middleware for every outgoing call (AI providers, the classifier,
 * Expo push): a client span with the method, host and status. Never the
 * path, query, headers (API keys) or bodies.
 *
 * The trace context is sent only to our own services (the classifier),
 * never to third parties.
 */
final readonly class TraceHttpClient
{
    /**
     * @param  list<string>  $propagateTo  hosts that receive the `traceparent` header
     */
    public function __construct(private Telemetry $telemetry, private array $propagateTo) {}

    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler): PromiseInterface {
            if (! $this->telemetry->enabled()) {
                return self::promise($handler($request, $options));
            }

            $host = $request->getUri()->getHost();
            $span = $this->telemetry->tracer()->spanBuilder("{$request->getMethod()} {$host}")
                ->setSpanKind(SpanKind::KIND_CLIENT)
                ->setAttribute('http.request.method', $request->getMethod())
                ->setAttribute('server.address', $host)
                ->startSpan();

            if (in_array($host, $this->propagateTo, true)) {
                $carrier = [];
                TraceContextPropagator::getInstance()->inject($carrier, null, $span->storeInContext(Context::getCurrent()));

                foreach (Carrier::headers($carrier) as $name => $value) {
                    $request = $request->withHeader($name, $value);
                }
            }

            $promise = self::promise($handler($request, $options));

            return $promise->then(
                function (ResponseInterface $response) use ($span): ResponseInterface {
                    $span->setAttribute('http.response.status_code', $response->getStatusCode());

                    if ($response->getStatusCode() >= 400) {
                        $span->setStatus(StatusCode::STATUS_ERROR);
                    }

                    $span->end();

                    return $response;
                },
                function (mixed $reason) use ($span): PromiseInterface {
                    $span->addEvent('exception', ['exception.type' => $reason instanceof Throwable ? $reason::class : 'unknown']);
                    $span->setStatus(StatusCode::STATUS_ERROR);
                    $span->end();

                    return Create::rejectionFor($reason);
                },
            );
        };
    }

    private static function promise(mixed $promise): PromiseInterface
    {
        return $promise instanceof PromiseInterface ? $promise : Create::promiseFor($promise);
    }
}
