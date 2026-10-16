<?php

declare(strict_types=1);

namespace App\Services\Knowledge;

final readonly class SearchHit
{
    /**
     * @param  int  $movement  Positions gained (+) or lost (-) against the purely semantic ranking
     */
    public function __construct(
        public string $chunkId,
        public string $articleId,
        public string $title,
        public int $ordinal,
        public string $heading,
        public string $content,
        public float $textScore,
        public float $semanticScore,
        public float $score,
        public int $movement,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'chunk_id' => $this->chunkId,
            'article_id' => $this->articleId,
            'title' => $this->title,
            'ordinal' => $this->ordinal,
            'heading' => $this->heading,
            'content' => $this->content,
            'text_score' => round($this->textScore, 3),
            'semantic_score' => round($this->semanticScore, 3),
            'score' => round($this->score, 3),
            'movement' => $this->movement,
        ];
    }
}
