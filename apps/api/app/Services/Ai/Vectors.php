<?php

declare(strict_types=1);

namespace App\Services\Ai;

final class Vectors
{
    /**
     * Scales a vector to unit length, so cosine distance is meaningful for
     * every provider (some return unnormalized vectors at reduced sizes).
     *
     * @param  list<float|int>  $vector
     * @return list<float>
     */
    public static function normalize(array $vector): array
    {
        $norm = sqrt(array_sum(array_map(fn (float|int $v): float => $v * $v, $vector)));

        if ($norm == 0.0) {
            // pgvector cannot compare zero vectors; use a fixed unit vector instead.
            $unit = array_fill(0, count($vector), 0.0);
            $unit[0] = 1.0;

            return $unit;
        }

        return array_map(fn (float|int $v): float => (float) $v / $norm, $vector);
    }

    /**
     * @return list<float>
     */
    public static function check(mixed $vector, int $dimensions = Embedder::DIMENSIONS): array
    {
        if (! is_array($vector) || count($vector) !== $dimensions || ! array_is_list($vector)) {
            throw AiUnavailable::invalidResponse("Expected an embedding of {$dimensions} numbers.");
        }

        foreach ($vector as $value) {
            if (! is_int($value) && ! is_float($value)) {
                throw AiUnavailable::invalidResponse('Embeddings must be numeric.');
            }
        }

        /** @var list<float|int> $vector */
        return self::normalize($vector);
    }
}
