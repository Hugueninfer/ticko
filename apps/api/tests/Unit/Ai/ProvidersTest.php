<?php

declare(strict_types=1);

use App\Services\Ai\AiUnavailable;
use App\Services\Ai\AiUsage;
use App\Services\Ai\Embedder;
use App\Services\Ai\EmbeddingTask;
use App\Services\Ai\MeasuredTextGenerator;
use App\Services\Ai\Providers\FakeProvider;
use App\Services\Ai\Providers\GeminiProvider;
use App\Services\Ai\Providers\OllamaProvider;
use App\Services\Ai\TextGenerator;
use App\Services\Ai\TextPrompt;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function prompt(): TextPrompt
{
    return new TextPrompt('rules', 'task', ['type' => 'object', 'properties' => ['answer' => ['type' => 'string']]]);
}

function vector(int $size = Embedder::DIMENSIONS, float $value = 2.0): array
{
    return array_fill(0, $size, $value);
}

describe('selection', function (): void {
    it('uses the offline fake by default', function (): void {
        // The text provider is wrapped to meter latency, tokens and cost.
        expect(app(TextGenerator::class))->toBeInstanceOf(MeasuredTextGenerator::class)
            ->and(app(TextGenerator::class)->provider())->toBe('fake')
            ->and(app(Embedder::class))->toBeInstanceOf(FakeProvider::class);
    });

    it('picks providers from config', function (string $text, string $embedding, string $embeddingClass): void {
        config(['ticko.ai.text_provider' => $text, 'ticko.ai.embedding_provider' => $embedding]);

        expect(app(TextGenerator::class)->provider())->toBe($text)
            ->and(app(Embedder::class))->toBeInstanceOf($embeddingClass);
    })->with([
        'gemini' => ['gemini', 'gemini', GeminiProvider::class],
        'groq + ollama embeddings' => ['groq', 'ollama', OllamaProvider::class],
        'ollama' => ['ollama', 'ollama', OllamaProvider::class],
    ]);

    it('rejects unknown providers', function (): void {
        config(['ticko.ai.text_provider' => 'openai', 'ticko.ai.embedding_provider' => 'groq']);

        expect(fn () => app(TextGenerator::class))->toThrow(InvalidArgumentException::class)
            ->and(fn () => app(Embedder::class))->toThrow(InvalidArgumentException::class);
    });
});

describe('gemini', function (): void {
    beforeEach(fn () => config(['ticko.ai.text_provider' => 'gemini', 'ticko.ai.embedding_provider' => 'gemini', 'ticko.ai.gemini.key' => 'g-key']));

    it('asks for JSON with the schema in Gemini\'s dialect and the key in a header', function (): void {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => '{"answer":"ok"}']]]]]])]);

        expect(app(TextGenerator::class)->generate(prompt()))->toBe('{"answer":"ok"}');

        Http::assertSent(function (Request $request): bool {
            expect($request->url())->toBe('https://generativelanguage.googleapis.com/v1beta/models/gemini-3.7-flash:generateContent')
                ->and($request->url())->not->toContain('g-key')
                ->and($request->header('x-goog-api-key'))->toBe(['g-key'])
                ->and($request['systemInstruction']['parts'][0]['text'])->toBe('rules')
                ->and($request['generationConfig']['responseMimeType'])->toBe('application/json')
                ->and($request['generationConfig']['responseSchema']['type'])->toBe('OBJECT')
                ->and($request['generationConfig']['responseSchema']['properties']['answer']['type'])->toBe('STRING');

            return true;
        });
    });

    it('keeps reasoning low so JSON answers are not cut off, and bills reasoning tokens as output', function (string $model, ?array $thinking): void {
        config(['ticko.ai.gemini.text_model' => $model]);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => '{"answer":"ok"}']]]]],
            'usageMetadata' => ['promptTokenCount' => 600, 'candidatesTokenCount' => 80, 'thoughtsTokenCount' => 40],
        ])]);

        app(TextGenerator::class)->generate(prompt());

        Http::assertSent(fn (Request $request): bool => ($request['generationConfig']['thinkingConfig'] ?? null) === $thinking);
        expect(app(AiUsage::class)->outputTokens())->toBe(120);
    })->with([
        'Gemini 3' => ['gemini-3.7-flash', ['thinkingLevel' => 'low']],
        'Gemini 2.5 Flash' => ['gemini-2.5-flash', ['thinkingBudget' => 0]],
        'Gemini 2.5 Pro (cannot turn it off)' => ['gemini-2.5-pro', null],
    ]);

    it('embeds in batches at 768 dimensions and normalizes', function (): void {
        Http::fake(['*batchEmbedContents' => fn (Request $request) => Http::response([
            'embeddings' => array_map(fn () => ['values' => vector()], $request['requests']),
        ])]);

        $vectors = app(Embedder::class)->embed(['a', 'b'], EmbeddingTask::Query);

        expect($vectors)->toHaveCount(2)
            ->and(array_sum(array_map(fn (float $v): float => $v * $v, $vectors[0])))->toEqualWithDelta(1.0, 0.0001)
            ->and(app(Embedder::class)->embeddingModel())->toBe('gemini/gemini-embedding-001');
        Http::assertSent(fn (Request $request): bool => $request['requests'][0]['taskType'] === 'RETRIEVAL_QUERY'
            && $request['requests'][0]['outputDimensionality'] === 768);
    });

    it('refuses embeddings of the wrong size or count', function (array $body): void {
        Http::fake(['*' => Http::response($body)]);

        expect(fn () => app(Embedder::class)->embed(['a'], EmbeddingTask::Document))->toThrow(AiUnavailable::class);
    })->with([
        'wrong size' => [['embeddings' => [['values' => vector(10)]]]],
        'missing' => [['embeddings' => []]],
        'not numbers' => [['embeddings' => [['values' => array_fill(0, 768, 'x')]]]],
    ]);

    it('needs a key', function (): void {
        config(['ticko.ai.gemini.key' => null]);

        expect(fn () => app(TextGenerator::class)->generate(prompt()))
            ->toThrow(fn (AiUnavailable $e) => expect($e->errorCode)->toBe('LLM_NOT_CONFIGURED'));
        Http::assertNothingSent();
    });

    it('reports a blocked answer', function (): void {
        Http::fake(['*' => Http::response(['candidates' => [['finishReason' => 'SAFETY']]])]);

        expect(fn () => app(TextGenerator::class)->generate(prompt()))->toThrow(AiUnavailable::class, 'SAFETY');
    });
});

describe('errors', function (): void {
    beforeEach(fn () => config(['ticko.ai.text_provider' => 'groq', 'ticko.ai.groq.key' => 'q-key']));

    it('maps provider failures to stable codes', function (Closure $response, string $code): void {
        Http::fake(['*' => $response]);

        expect(fn () => app(TextGenerator::class)->generate(prompt()))
            ->toThrow(fn (AiUnavailable $e) => expect($e->errorCode)->toBe($code));
    })->with([
        'rate limited' => [fn () => Http::response([], 429), 'LLM_RATE_LIMITED'],
        'server error' => [fn () => Http::response([], 500), 'LLM_ERROR'],
        'timeout' => [fn () => throw new ConnectionException('timed out'), 'LLM_TIMEOUT'],
        'not JSON' => [fn () => Http::response('<html>'), 'LLM_INVALID_RESPONSE'],
        'empty answer' => [fn () => Http::response(['choices' => []]), 'LLM_INVALID_RESPONSE'],
    ]);
});

it('calls Groq\'s OpenAI-compatible API in JSON mode', function (): void {
    config(['ticko.ai.text_provider' => 'groq', 'ticko.ai.groq.key' => 'q-key']);
    Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => '{"answer":"ok"}']]]])]);

    $groq = app(TextGenerator::class);

    expect($groq->generate(prompt()))->toBe('{"answer":"ok"}')
        ->and($groq->provider())->toBe('groq')
        ->and($groq->model())->toBe('llama-3.3-70b-versatile');
    Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer q-key')
        && $request['response_format'] === ['type' => 'json_object']
        && $request['messages'][0] === ['role' => 'system', 'content' => 'rules']);
});

it('talks to a local Ollama for text and embeddings', function (): void {
    config(['ticko.ai.text_provider' => 'ollama', 'ticko.ai.embedding_provider' => 'ollama', 'ticko.ai.ollama.url' => 'http://ollama.test:11434/']);
    Http::fake([
        'ollama.test:11434/api/chat' => Http::response(['message' => ['content' => '{"answer":"ok"}']]),
        'ollama.test:11434/api/embed' => Http::response(['embeddings' => [vector(), vector()]]),
    ]);

    expect(app(TextGenerator::class)->generate(prompt()))->toBe('{"answer":"ok"}')
        ->and(app(Embedder::class)->embed(['a', 'b'], EmbeddingTask::Document))->toHaveCount(2)
        ->and(app(Embedder::class)->embed([], EmbeddingTask::Document))->toBe([])
        ->and(app(Embedder::class)->embeddingModel())->toBe('ollama/nomic-embed-text')
        ->and(app(TextGenerator::class)->provider())->toBe('ollama');
    Http::assertSent(fn (Request $request): bool => $request->url() === 'http://ollama.test:11434/api/chat'
        && $request['format'] === prompt()->schema && $request['stream'] === false);
});

it('embeds offline with the fake: similar words land close together', function (): void {
    $fake = new FakeProvider;
    [$a, $b, $c, $empty] = $fake->embed(['rastreio do pedido', 'rastreamento de pedidos', 'cupom de desconto', 'a o de'], EmbeddingTask::Document);
    $cosine = fn (array $x, array $y): float => array_sum(array_map(fn ($i, $j) => $i * $j, $x, $y));

    expect($cosine($a, $b))->toBeGreaterThan($cosine($a, $c))
        ->and($empty[0])->toBe(1.0)
        ->and(json_decode($fake->generate(new TextPrompt('s', 'u')), true))->toBe(['answerable' => false, 'answer' => '', 'citations' => []]);
});
