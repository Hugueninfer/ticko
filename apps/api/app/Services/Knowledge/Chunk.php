<?php

declare(strict_types=1);

namespace App\Services\Knowledge;

final readonly class Chunk
{
    public function __construct(
        public int $ordinal,
        public string $heading,
        public string $content,
        public int $tokens,
    ) {}

    /** What the embedding model sees: where the passage sits, then the passage. */
    public function embeddingInput(string $title): string
    {
        return "{$title} › {$this->heading}\n{$this->content}";
    }
}
