<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Ai\Embedder;
use App\Services\Ai\Providers\FakeProvider;
use App\Services\Ai\Providers\GeminiProvider;
use App\Services\Ai\Providers\GroqProvider;
use App\Services\Ai\Providers\HttpJson;
use App\Services\Ai\Providers\OllamaProvider;
use App\Services\Ai\TextGenerator;
use App\Services\Classifier\ClassifierClient;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

/**
 * Picks the AI providers from config (TICKO_AI_TEXT_PROVIDER and
 * TICKO_AI_EMBEDDING_PROVIDER). Everything else depends on the
 * TextGenerator and Embedder interfaces only.
 */
final class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TextGenerator::class, fn (Application $app): TextGenerator => match ($this->setting('text_provider')) {
            'gemini' => $this->gemini($app),
            'groq' => new GroqProvider(
                $this->http($app),
                $this->setting('groq.url'),
                $this->secret('groq.key'),
                $this->setting('groq.text_model'),
            ),
            'ollama' => $this->ollama($app),
            'fake' => new FakeProvider,
            default => throw new InvalidArgumentException('TICKO_AI_TEXT_PROVIDER must be fake, gemini, groq or ollama.'),
        });

        $this->app->bind(ClassifierClient::class, fn (Application $app): ClassifierClient => $this->classifier($app));

        $this->app->bind(Embedder::class, fn (Application $app): Embedder => match ($this->setting('embedding_provider')) {
            'gemini' => $this->gemini($app),
            'ollama' => $this->ollama($app),
            'fake' => new FakeProvider,
            default => throw new InvalidArgumentException('TICKO_AI_EMBEDDING_PROVIDER must be fake, gemini or ollama.'),
        });
    }

    private function classifier(Application $app): ClassifierClient
    {
        return new ClassifierClient(
            $app->make(HttpFactory::class),
            config()->string('ticko.classifier.url'),
            config()->string('ticko.classifier.token'),
            config()->integer('ticko.classifier.timeout_seconds'),
        );
    }

    private function gemini(Application $app): GeminiProvider
    {
        return new GeminiProvider(
            $this->http($app),
            $this->setting('gemini.url'),
            $this->secret('gemini.key'),
            $this->setting('gemini.text_model'),
            $this->setting('gemini.embedding_model'),
        );
    }

    private function ollama(Application $app): OllamaProvider
    {
        return new OllamaProvider(
            $this->http($app),
            rtrim($this->setting('ollama.url'), '/'),
            $this->setting('ollama.text_model'),
            $this->setting('ollama.embedding_model'),
        );
    }

    private function http(Application $app): HttpJson
    {
        return new HttpJson($app->make(HttpFactory::class), config()->integer('ticko.ai.timeout_seconds'));
    }

    private function setting(string $key): string
    {
        return config()->string("ticko.ai.{$key}");
    }

    private function secret(string $key): ?string
    {
        $value = config("ticko.ai.{$key}");

        return is_string($value) && $value !== '' ? $value : null;
    }
}
