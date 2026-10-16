<?php

declare(strict_types=1);

namespace App\Services\Classifier;

use App\Enums\Sentiment;
use App\Enums\TicketPriority;

/** A validated answer of the classifier service. */
final readonly class Classification
{
    /**
     * @param  list<string>  $flags
     */
    public function __construct(
        public string $model,
        public string $category,
        public float $categoryConfidence,
        public TicketPriority $priority,
        public float $priorityConfidence,
        public Sentiment $sentiment,
        public float $sentimentConfidence,
        public array $flags,
    ) {}
}
