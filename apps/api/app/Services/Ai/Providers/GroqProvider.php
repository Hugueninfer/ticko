<?php

declare(strict_types=1);

namespace App\Services\Ai\Providers;

use App\Services\Ai\AiUnavailable;
use App\Services\Ai\AiUsage;
use App\Services\Ai\TextGenerator;
use App\Services\Ai\TextPrompt;

/**
 * Groq (OpenAI-compatible chat completions). Text only: Groq has no
 * embedding models, so pair it with another embedding provider.
 */
final readonly class GroqProvider implements TextGenerator
{
    public function __construct(
        private HttpJson $http,
        private string $url,
        private ?string $key,
        private string $textModel,
        private ?AiUsage $usage = null,
    ) {}

    public function generate(TextPrompt $prompt): string
    {
        if ($this->key === null || $this->key === '') {
            throw AiUnavailable::notConfigured('groq');
        }

        $payload = [
            'model' => $this->textModel,
            'messages' => [
                ['role' => 'system', 'content' => $prompt->system],
                ['role' => 'user', 'content' => $prompt->user],
            ],
            'temperature' => $prompt->temperature,
            'max_tokens' => $prompt->maxOutputTokens,
        ];

        if ($prompt->schema !== null) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $json = $this->http->post("{$this->url}/chat/completions", ['Authorization' => "Bearer {$this->key}"], $payload);
        $text = data_get($json, 'choices.0.message.content');

        if (! is_string($text) || $text === '') {
            throw AiUnavailable::invalidResponse('Groq returned no text.');
        }

        $input = data_get($json, 'usage.prompt_tokens');
        $output = data_get($json, 'usage.completion_tokens');
        $this->usage?->record(is_int($input) ? $input : 0, is_int($output) ? $output : 0);

        return $text;
    }

    public function provider(): string
    {
        return 'groq';
    }

    public function model(): string
    {
        return $this->textModel;
    }
}
