<?php

declare(strict_types=1);

namespace App\Support\Telemetry;

use App\Support\Tenancy\TenantContext;
use LogicException;
use OpenTelemetry\API\Metrics\CounterInterface;
use OpenTelemetry\API\Metrics\GaugeInterface;
use OpenTelemetry\API\Metrics\HistogramInterface;

/**
 * Every metric Ticko records, in one place.
 *
 * Cardinality rules: attributes come from closed sets (route templates,
 * enum values, job classes, providers) or the tenant slug. Never a user,
 * ticket, e-mail or free text. Prometheus names: dots become underscores,
 * plus the unit and `_total` for counters (ticko_ai_tokens_total...).
 */
final class Metrics
{
    /** Request latency buckets (seconds): 5 ms to 10 s. */
    private const array SECONDS = [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10];

    /** Slower work (jobs, model calls): 50 ms to 2 min. */
    private const array SLOW_SECONDS = [0.05, 0.1, 0.25, 0.5, 1, 2, 5, 10, 20, 30, 60, 120];

    private const array CONFIDENCE = [0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 0.95, 1];

    /** @var array<string, CounterInterface|HistogramInterface|GaugeInterface> */
    private array $instruments = [];

    public function __construct(private readonly Telemetry $telemetry, private readonly TenantContext $tenants) {}

    public function httpRequest(string $method, string $route, int $status, float $seconds): void
    {
        $this->histogram('ticko.http.server.duration', 's', 'API request duration', self::SECONDS)
            ->record($seconds, ['http.request.method' => $method, 'http.route' => $route, 'http.response.status_code' => $status]);
    }

    public function job(string $job, string $outcome, float $seconds): void
    {
        $this->histogram('ticko.queue.job.duration', 's', 'Queued job duration by outcome (processed, failed, released)', self::SLOW_SECONDS)
            ->record($seconds, ['job' => $job, 'outcome' => $outcome]);
    }

    public function loginFailure(): void
    {
        $this->counter('ticko.auth.login.failures', 'Failed sign-in attempts')->add(1, $this->tenant());
    }

    public function llmCall(string $provider, string $model, string $outcome, float $seconds): void
    {
        $this->histogram('ticko.ai.llm.duration', 's', 'Text generation calls by provider, model and outcome', self::SLOW_SECONDS)
            ->record($seconds, ['provider' => $provider, 'model' => $model, 'outcome' => $outcome]);
    }

    public function llmUsage(string $provider, string $model, int $inputTokens, int $outputTokens, float $costUsd): void
    {
        $attributes = ['provider' => $provider, 'model' => $model] + $this->tenant();
        $tokens = $this->counter('ticko.ai.tokens', 'Model tokens by type (input, output)', '{token}');
        $tokens->add($inputTokens, $attributes + ['type' => 'input']);
        $tokens->add($outputTokens, $attributes + ['type' => 'output']);
        $this->counter('ticko.ai.cost', 'Estimated model cost', 'USD')->add($costUsd, $attributes);
    }

    /** One finished suggestion: ready, insufficient ("I don't know") or failed. */
    public function suggestion(string $outcome): void
    {
        $this->counter('ticko.ai.suggestions', 'Reply suggestions by outcome')->add(1, ['outcome' => $outcome] + $this->tenant());
    }

    /** An agent's review of a draft: approved (as is or edited) or discarded (with the reason). */
    public function suggestionReview(string $result, string $reason = 'none'): void
    {
        $this->counter('ticko.ai.suggestion.reviews', 'Agent reviews of AI drafts')->add(1, ['result' => $result, 'reason' => $reason] + $this->tenant());
    }

    public function classifierCall(string $outcome, float $seconds): void
    {
        $this->histogram('ticko.classifier.duration', 's', 'Classifier calls by outcome', self::SLOW_SECONDS)
            ->record($seconds, ['outcome' => $outcome]);
    }

    public function classifierLabel(string $task, string $label, float $confidence, bool $applied): void
    {
        $this->histogram('ticko.classifier.confidence', '1', 'Classifier confidence by task and label', self::CONFIDENCE)
            ->record($confidence, ['task' => $task, 'label' => $label, 'applied' => $applied ? 'yes' : 'no']);
    }

    public function classificationCorrection(string $field, string $setBy): void
    {
        $this->counter('ticko.classifier.corrections', 'Agent changes to classified fields (set_by: who had set the value)')
            ->add(1, ['field' => $field, 'set_by' => $setBy] + $this->tenant());
    }

    /**
     * @param  array<non-empty-string, string>  $attributes
     */
    public function gauge(string $name, string $description, int|float $value, array $attributes): void
    {
        $instrument = $this->instruments[$name] ??= $this->telemetry->meter()->createGauge($name, '1', $description);

        if ($instrument instanceof GaugeInterface) {
            $instrument->record($value, $attributes);
        }
    }

    /**
     * @return array{tenant?: string}
     */
    private function tenant(): array
    {
        $slug = $this->tenants->tenant()?->slug;

        return $slug === null ? [] : ['tenant' => $slug];
    }

    private function counter(string $name, string $description, string $unit = '1'): CounterInterface
    {
        $instrument = $this->instruments[$name] ??= $this->telemetry->meter()->createCounter($name, $unit, $description);

        return $instrument instanceof CounterInterface ? $instrument : throw new LogicException("{$name} is not a counter.");
    }

    /**
     * @param  list<int|float>  $buckets
     */
    private function histogram(string $name, string $unit, string $description, array $buckets): HistogramInterface
    {
        $instrument = $this->instruments[$name] ??= $this->telemetry->meter()->createHistogram(
            $name, $unit, $description, ['ExplicitBucketBoundaries' => $buckets],
        );

        return $instrument instanceof HistogramInterface ? $instrument : throw new LogicException("{$name} is not a histogram.");
    }
}
