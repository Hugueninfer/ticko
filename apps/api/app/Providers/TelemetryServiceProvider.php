<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\Telemetry\Metrics;
use App\Support\Telemetry\Telemetry;
use App\Support\Telemetry\TraceHttpClient;
use App\Support\Telemetry\TraceJobs;
use App\Support\Telemetry\TraceRequests;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Queue\Events\JobAttempted;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Queue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * OpenTelemetry for the API, the queue workers and the scheduler (see
 * App\Support\Telemetry). Off unless TICKO_TELEMETRY=true (`make up-obs`);
 * the hooks below check at run time, so tests can swap in an in-memory
 * Telemetry instance.
 */
final class TelemetryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Telemetry::class, fn (Application $app): Telemetry => self::fromConfig($app));
        $this->app->singleton(Metrics::class, fn (Application $app): Metrics => new Metrics($app->make(Telemetry::class), $app->make(TenantContext::class)));
        // The same instance handles the request and its termination (flush).
        $this->app->singleton(TraceRequests::class, fn (Application $app): TraceRequests => new TraceRequests(
            $app->make(Telemetry::class), $app->make(Metrics::class), $app->make(TenantContext::class),
        ));
        $this->app->singleton(TraceJobs::class, fn (Application $app): TraceJobs => new TraceJobs($app->make(Telemetry::class), $app->make(Metrics::class)));
    }

    public function boot(): void
    {
        Queue::createPayloadUsing(fn (): array => $this->app->make(TraceJobs::class)->payload());
        Event::listen(JobProcessing::class, fn (JobProcessing $event) => $this->app->make(TraceJobs::class)->starting($event));
        Event::listen(JobAttempted::class, fn (JobAttempted $event) => $this->app->make(TraceJobs::class)->finished($event));

        // Only our own classifier receives the trace context.
        $this->app->make(HttpFactory::class)->globalMiddleware(function (callable $handler): callable {
            $classifier = parse_url(config()->string('ticko.classifier.url'), PHP_URL_HOST);

            return (new TraceHttpClient($this->app->make(Telemetry::class), is_string($classifier) ? [$classifier] : []))($handler);
        });

        // Commands (the scheduler) flush when the process ends.
        $this->app->terminating(fn () => $this->app->make(Telemetry::class)->flush());
    }

    private static function fromConfig(Application $app): Telemetry
    {
        if (! config()->boolean('ticko.telemetry.enabled') || $app->runningUnitTests()) {
            return Telemetry::disabled();
        }

        return Telemetry::otlp(config()->string('ticko.telemetry.endpoint'), [
            'service.name' => config()->string('ticko.telemetry.service'),
            'service.namespace' => 'ticko',
            'service.version' => '1.0.0',
            'deployment.environment.name' => (string) $app->environment(),
        ], config()->float('ticko.telemetry.timeout_seconds'));
    }
}
