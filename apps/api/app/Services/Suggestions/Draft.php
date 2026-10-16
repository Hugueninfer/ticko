<?php

declare(strict_types=1);

namespace App\Services\Suggestions;

use App\Services\Knowledge\SearchHit;

/** The outcome of asking the model for a reply draft. */
final readonly class Draft
{
    /**
     * @param  list<int>  $cited  1-based numbers of the sources the answer cites
     * @param  list<SearchHit>  $sources
     */
    public function __construct(
        public bool $answerable,
        public string $answer,
        public array $cited,
        public array $sources,
        public float $confidence,
    ) {}
}
