<?php

declare(strict_types=1);

namespace App\Services\Ai\Providers;

use App\Services\Ai\AiUnavailable;
use App\Services\Ai\Embedder;
use App\Services\Ai\EmbeddingTask;
use App\Services\Ai\TextGenerator;
use App\Services\Ai\TextPrompt;
use App\Services\Ai\Vectors;

/**
 * Google Gemini API: generateContent (JSON output with a response schema)
 * and batchEmbedContents (gemini-embedding-001 at 768 dimensions).
 */
final readonly class GeminiProvider implements Embedder, TextGenerator
{
    private const int EMBED_BATCH = 100;

    public function __construct(
        private HttpJson $http,
        private string $url,
        private ?string $key,
        private string $textModel,
        private string $embeddingModel,
    ) {}

    public function generate(TextPrompt $prompt): string
    {
        $config = ['temperature' => $prompt->temperature, 'maxOutputTokens' => $prompt->maxOutputTokens];

        if ($prompt->schema !== null) {
            $config['responseMimeType'] = 'application/json';
            $config['responseSchema'] = self::schema($prompt->schema);
        }

        $json = $this->http->post("{$this->url}/models/{$this->textModel}:generateContent", $this->headers(), [
            'systemInstruction' => ['parts' => [['text' => $prompt->system]]],
            'contents' => [['role' => 'user', 'parts' => [['text' => $prompt->user]]]],
            'generationConfig' => $config,
        ]);

        $text = data_get($json, 'candidates.0.content.parts.0.text');

        if (! is_string($text) || $text === '') {
            $reason = data_get($json, 'candidates.0.finishReason');

            throw AiUnavailable::invalidResponse('Gemini returned no text ('.(is_string($reason) ? $reason : 'unknown').').');
        }

        return $text;
    }

    public function embed(array $texts, EmbeddingTask $task): array
    {
        $vectors = [];

        foreach (array_chunk($texts, self::EMBED_BATCH) as $batch) {
            $json = $this->http->post("{$this->url}/models/{$this->embeddingModel}:batchEmbedContents", $this->headers(), [
                'requests' => array_map(fn (string $text): array => [
                    'model' => "models/{$this->embeddingModel}",
                    'content' => ['parts' => [['text' => $text]]],
                    'taskType' => $task === EmbeddingTask::Query ? 'RETRIEVAL_QUERY' : 'RETRIEVAL_DOCUMENT',
                    'outputDimensionality' => Embedder::DIMENSIONS,
                ], $batch),
            ]);

            $embeddings = $json['embeddings'] ?? null;

            if (! is_array($embeddings) || count($embeddings) !== count($batch)) {
                throw AiUnavailable::invalidResponse('Gemini returned the wrong number of embeddings.');
            }

            foreach ($embeddings as $embedding) {
                $vectors[] = Vectors::check(is_array($embedding) ? ($embedding['values'] ?? null) : null);
            }
        }

        return $vectors;
    }

    public function provider(): string
    {
        return 'gemini';
    }

    public function model(): string
    {
        return $this->textModel;
    }

    public function embeddingModel(): string
    {
        return "gemini/{$this->embeddingModel}";
    }

    /**
     * Gemini's schema dialect spells types in upper case ("OBJECT", "STRING").
     *
     * @param  array<mixed>  $schema
     * @return array<mixed>
     */
    private static function schema(array $schema): array
    {
        $converted = [];

        foreach ($schema as $key => $value) {
            $converted[$key] = match (true) {
                $key === 'type' && is_string($value) => mb_strtoupper($value),
                is_array($value) => self::schema($value),
                default => $value,
            };
        }

        return $converted;
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        if ($this->key === null || $this->key === '') {
            throw AiUnavailable::notConfigured('gemini');
        }

        // Header, not query string, so the key never ends up in URLs or logs.
        return ['x-goog-api-key' => $this->key];
    }
}
