<?php

declare(strict_types=1);

namespace App\Services\Ai\Providers;

use App\Services\Ai\AiUnavailable;
use App\Services\Ai\AiUsage;
use App\Services\Ai\Embedder;
use App\Services\Ai\EmbeddingTask;
use App\Services\Ai\TextGenerator;
use App\Services\Ai\TextPrompt;
use App\Services\Ai\Vectors;

/**
 * A local Ollama server: nothing leaves your machine. The embedding model
 * must produce 768-dimension vectors (e.g. nomic-embed-text).
 */
final readonly class OllamaProvider implements Embedder, TextGenerator
{
    public function __construct(
        private HttpJson $http,
        private string $url,
        private string $textModel,
        private string $embeddingModel,
        private ?AiUsage $usage = null,
    ) {}

    public function generate(TextPrompt $prompt): string
    {
        $payload = [
            'model' => $this->textModel,
            'messages' => [
                ['role' => 'system', 'content' => $prompt->system],
                ['role' => 'user', 'content' => $prompt->user],
            ],
            'stream' => false,
            'options' => ['temperature' => $prompt->temperature, 'num_predict' => $prompt->maxOutputTokens],
        ];

        if ($prompt->schema !== null) {
            $payload['format'] = $prompt->schema;
        }

        $json = $this->http->post("{$this->url}/api/chat", [], $payload);

        $text = data_get($json, 'message.content');

        if (! is_string($text) || $text === '') {
            throw AiUnavailable::invalidResponse('Ollama returned no text.');
        }

        $input = data_get($json, 'prompt_eval_count');
        $output = data_get($json, 'eval_count');
        $this->usage?->record(is_int($input) ? $input : 0, is_int($output) ? $output : 0);

        return $text;
    }

    public function embed(array $texts, EmbeddingTask $task): array
    {
        if ($texts === []) {
            return [];
        }

        $json = $this->http->post("{$this->url}/api/embed", [], ['model' => $this->embeddingModel, 'input' => $texts]);
        $embeddings = $json['embeddings'] ?? null;

        if (! is_array($embeddings) || count($embeddings) !== count($texts)) {
            throw AiUnavailable::invalidResponse('Ollama returned the wrong number of embeddings.');
        }

        return array_values(array_map(fn (mixed $embedding): array => Vectors::check($embedding), $embeddings));
    }

    public function provider(): string
    {
        return 'ollama';
    }

    public function model(): string
    {
        return $this->textModel;
    }

    public function embeddingModel(): string
    {
        return "ollama/{$this->embeddingModel}";
    }
}
