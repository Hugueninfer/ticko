<?php

declare(strict_types=1);

namespace App\Services\Ai;

final readonly class TextPrompt
{
    /**
     * @param  string  $system  Rules, from the application only
     * @param  string  $user  The task, with untrusted data inside delimiters
     * @param  array<string, mixed>|null  $schema  JSON schema of the expected answer (OpenAPI subset)
     * @param  list<string>  $sources  The passages included in $user (used by the offline fake only)
     */
    public function __construct(
        public string $system,
        public string $user,
        public ?array $schema = null,
        public int $maxOutputTokens = 900,
        public float $temperature = 0.2,
        public array $sources = [],
    ) {}
}
