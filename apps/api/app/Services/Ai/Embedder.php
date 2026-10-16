<?php

declare(strict_types=1);

namespace App\Services\Ai;

/**
 * Turns texts into unit-length vectors of {@see self::DIMENSIONS} floats
 * (the size of the kb_chunks.embedding column).
 */
interface Embedder
{
    public const int DIMENSIONS = 768;

    /**
     * @param  list<string>  $texts
     * @return list<list<float>>
     *
     * @throws AiUnavailable
     */
    public function embed(array $texts, EmbeddingTask $task): array;

    /** Identifies the vector space; chunks embedded with another model are ignored by search. */
    public function embeddingModel(): string;
}
