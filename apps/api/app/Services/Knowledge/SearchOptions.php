<?php

declare(strict_types=1);

namespace App\Services\Knowledge;

final readonly class SearchOptions
{
    /**
     * @param  float  $textWeight  0..1, weight of the full-text score (the rest is semantic)
     * @param  bool  $rerank  Fuse both rankings with reciprocal rank fusion instead of weighting scores
     * @param  bool  $customerVisibleOnly  Only articles customers may be told about (reply drafting)
     */
    public function __construct(
        public float $textWeight = 0.4,
        public int $topK = 5,
        public bool $rerank = false,
        public ?string $categoryId = null,
        public bool $customerVisibleOnly = false,
    ) {}
}
