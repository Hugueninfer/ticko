<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Support\Telemetry\Metrics;
use App\Support\Telemetry\Telemetry;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;

/**
 * Wraps the configured text provider: one span per model call (provider,
 * model, tokens; never the prompt or the answer), latency and outcome
 * metrics, token counters and the estimated cost per tenant.
 */
final readonly class MeasuredTextGenerator implements TextGenerator
{
    /**
     * @param  array{float, float}  $price  US dollars per million [input, output] tokens
     */
    public function __construct(
        private TextGenerator $inner,
        private AiUsage $usage,
        private Telemetry $telemetry,
        private Metrics $metrics,
        private array $price,
    ) {}

    public function generate(TextPrompt $prompt): string
    {
        $span = $this->telemetry->tracer()->spanBuilder("chat {$this->inner->model()}")
            ->setSpanKind(SpanKind::KIND_CLIENT)
            ->setAttribute('gen_ai.operation.name', 'chat')
            ->setAttribute('gen_ai.system', $this->inner->provider())
            ->setAttribute('gen_ai.request.model', $this->inner->model())
            ->startSpan();
        $scope = $span->activate();
        $started = hrtime(true);
        $this->usage->takeLast();

        try {
            $text = $this->inner->generate($prompt);
        } catch (AiUnavailable $exception) {
            $span->setAttribute('error.type', $exception->errorCode)->setStatus(StatusCode::STATUS_ERROR)->end();
            $this->metrics->llmCall($this->inner->provider(), $this->inner->model(), strtolower($exception->errorCode), self::seconds($started));

            throw $exception;
        } finally {
            $scope->detach();
        }

        [$input, $output] = $this->usage->takeLast();
        $cost = ($input * $this->price[0] + $output * $this->price[1]) / 1_000_000;
        $this->usage->addCost($cost);

        $span->setAttribute('gen_ai.usage.input_tokens', $input)->setAttribute('gen_ai.usage.output_tokens', $output)->end();
        $this->metrics->llmCall($this->inner->provider(), $this->inner->model(), 'ok', self::seconds($started));
        $this->metrics->llmUsage($this->inner->provider(), $this->inner->model(), $input, $output, $cost);

        return $text;
    }

    public function provider(): string
    {
        return $this->inner->provider();
    }

    public function model(): string
    {
        return $this->inner->model();
    }

    private static function seconds(int $started): float
    {
        return (hrtime(true) - $started) / 1e9;
    }
}
