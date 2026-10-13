<?php

declare(strict_types=1);

namespace App\Services;

final readonly class SanitizedImage
{
    public function __construct(
        public string $contents,
        public string $mimeType,
        public string $extension,
        public int $width,
        public int $height,
    ) {}

    public function size(): int
    {
        return strlen($this->contents);
    }

    public function checksum(): string
    {
        return hash('sha256', $this->contents);
    }
}
