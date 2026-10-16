<?php

declare(strict_types=1);

namespace App\Services\Ai\Providers;

use App\Services\Ai\AiUsage;
use App\Services\Ai\Embedder;
use App\Services\Ai\EmbeddingTask;
use App\Services\Ai\TextGenerator;
use App\Services\Ai\TextPrompt;
use App\Services\Ai\Vectors;
use Illuminate\Support\Str;

/**
 * Offline, deterministic provider for development, demos, tests and CI:
 *
 *  - embeddings: hashed word stems (feature hashing), so passages that
 *    share words are close; no semantics, but realistic enough to search;
 *  - text: an extractive answer quoting the first sentences of the best
 *    passages, with citations, in the JSON shape the real models return.
 *
 * Nothing leaves the machine.
 */
final class FakeProvider implements Embedder, TextGenerator
{
    /** @var list<string> */
    private const array STOP_WORDS = [
        'a', 'o', 'as', 'os', 'de', 'do', 'da', 'dos', 'das', 'e', 'em', 'no', 'na', 'nos', 'nas', 'um', 'uma',
        'por', 'para', 'com', 'que', 'se', 'meu', 'minha', 'seu', 'sua', 'ao', 'the', 'and', 'of', 'to', 'is',
        'it', 'my', 'nao', 'mais', 'ja', 'faz', 'eu', 'voce', 'ou',
    ];

    public function __construct(private readonly ?AiUsage $usage = null) {}

    public function generate(TextPrompt $prompt): string
    {
        $sentences = [];

        foreach (array_slice($prompt->sources, 0, 2) as $index => $source) {
            $first = trim((preg_split('/(?<=[.!?])\s+/u', trim($source)) ?: [''])[0]);

            if ($first !== '') {
                $sentences[] = rtrim($first, '.').' ['.($index + 1).'].';
            }
        }

        $answer = (string) json_encode($sentences === []
            ? ['answerable' => false, 'answer' => '', 'citations' => []]
            : [
                'answerable' => true,
                'answer' => 'Olá! '.implode(' ', $sentences),
                'citations' => array_keys(array_fill(1, count($sentences), true)),
            ], JSON_UNESCAPED_UNICODE);

        // No real model: report an estimate so usage and cost dashboards work offline.
        $this->usage?->record(AiUsage::estimate($prompt->system.$prompt->user), AiUsage::estimate($answer));

        return $answer;
    }

    public function embed(array $texts, EmbeddingTask $task): array
    {
        return array_map(fn (string $text): array => $this->vector($text), $texts);
    }

    public function provider(): string
    {
        return 'fake';
    }

    public function model(): string
    {
        return 'fake-extractive';
    }

    public function embeddingModel(): string
    {
        return 'fake/hashed-stems-768';
    }

    /**
     * @return list<float>
     */
    private function vector(string $text): array
    {
        $vector = array_fill(0, Embedder::DIMENSIONS, 0.0);
        $words = preg_split('/[^a-z0-9]+/', Str::lower(Str::ascii($text)), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($words as $word) {
            if (mb_strlen($word) < 3 || in_array($word, self::STOP_WORDS, true)) {
                continue;
            }

            // Crude stemming: "rastreio" and "rastreamento" share "rastr".
            $hash = crc32(mb_substr($word, 0, 5));
            $vector[$hash % Embedder::DIMENSIONS] += ($hash & 0x80000000) !== 0 ? -1.0 : 1.0;
        }

        return Vectors::normalize(array_values($vector));
    }
}
