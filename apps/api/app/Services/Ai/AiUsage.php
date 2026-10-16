<?php

declare(strict_types=1);

namespace App\Services\Ai;

/**
 * Token usage reported by the text providers, collected per request or job
 * (a scoped binding), so callers can store and meter it.
 */
final class AiUsage
{
    private int $inputTokens = 0;

    private int $outputTokens = 0;

    private float $costUsd = 0.0;

    /** @var array{int, int} the last call's [input, output] tokens */
    private array $last = [0, 0];

    public function record(int $inputTokens, int $outputTokens): void
    {
        $this->last = [max(0, $inputTokens), max(0, $outputTokens)];
        $this->inputTokens += $this->last[0];
        $this->outputTokens += $this->last[1];
    }

    public function addCost(float $usd): void
    {
        $this->costUsd += $usd;
    }

    /**
     * @return array{int, int}
     */
    public function takeLast(): array
    {
        [$last, $this->last] = [$this->last, [0, 0]];

        return $last;
    }

    public function inputTokens(): int
    {
        return $this->inputTokens;
    }

    public function outputTokens(): int
    {
        return $this->outputTokens;
    }

    public function costUsd(): float
    {
        return $this->costUsd;
    }

    public function reset(): void
    {
        $this->inputTokens = $this->outputTokens = 0;
        $this->costUsd = 0.0;
        $this->last = [0, 0];
    }

    /** Rough estimate for providers that report nothing (about 4 characters per token). */
    public static function estimate(string $text): int
    {
        return (int) ceil(mb_strlen($text) / 4);
    }
}
