<?php

declare(strict_types=1);

namespace App\Services\Ai;

use RuntimeException;
use Throwable;

/**
 * The AI provider could not answer. The code is stored and shown to agents
 * (e.g. "LLM_TIMEOUT"); the message never contains keys or prompt content.
 */
final class AiUnavailable extends RuntimeException
{
    public function __construct(public readonly string $errorCode, string $message = '', ?Throwable $previous = null)
    {
        parent::__construct($message !== '' ? $message : $errorCode, 0, $previous);
    }

    public static function notConfigured(string $provider): self
    {
        return new self('LLM_NOT_CONFIGURED', "The {$provider} provider is not configured.");
    }

    public static function timeout(?Throwable $previous = null): self
    {
        return new self('LLM_TIMEOUT', 'The AI provider did not answer in time.', $previous);
    }

    public static function rateLimited(): self
    {
        return new self('LLM_RATE_LIMITED', 'The AI provider rate limit was reached.');
    }

    public static function failed(int $status): self
    {
        return new self('LLM_ERROR', "The AI provider answered with HTTP {$status}.");
    }

    public static function invalidResponse(string $why): self
    {
        return new self('LLM_INVALID_RESPONSE', $why);
    }
}
