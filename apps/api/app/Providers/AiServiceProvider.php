<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Ai\AiUsage;
use App\Services\Ai\Embedder;
use App\Services\Ai\MeasuredTextGenerator;
use App\Services\Ai\Providers\FakeProvider;
use App\Services\Ai\Providers\GeminiProvider;
use App\Services\Ai\Providers\GroqProvider;
use App\Services\Ai\Providers\HttpJson;
use App\Services\Ai\Providers\OllamaProvider;
use App\Services\Ai\TextGenerator;
use App\Services\Classifier\ClassifierClient;
use App\Support\Telemetry\Metrics;
use App\Support\Telemetry\Telemetry;
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
        // Token usage of the current request or job.
        $this->app->scoped(AiUsage::class);

        $this->app->bind(TextGenerator::class, function (Application $app): TextGenerator {
            $usage = $app->make(AiUsage::class);
            $provider = $this->setting('text_provider');
            $inner = match ($provider) {
                'gemini' => $this->gemini($app, $usage),
                'groq' => new GroqProvider(
                    $this->http($app),
                    $this->setting('groq.url'),
                    $this->secret('groq.key'),
                    $this->setting('groq.text_model'),
                    $usage,
                ),
                'ollama' => $this->ollama($app, $usage),
                'fake' => new FakeProvider($usage),
                default => throw new InvalidArgumentException('TICKO_AI_TEXT_PROVIDER must be fake, gemini, groq or ollama.'),
            };
            $price = config()->array("ticko.ai.prices.{$provider}", [0.0, 0.0]);

            return new MeasuredTextGenerator(
                $inner,
                $usage,
                $app->make(Telemetry::class),
                $app->make(Metrics::class),
                [is_numeric($price[0] ?? null) ? (float) $price[0] : 0.0, is_numeric($price[1] ?? null) ? (float) $price[1] : 0.0],
            );
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
            $app->make(Metrics::class),
        );
    }

    private function gemini(Application $app, ?AiUsage $usage = null): GeminiProvider
    {
        return new GeminiProvider(
            $this->http($app),
            $this->setting('gemini.url'),
            $this->secret('gemini.key'),
            $this->setting('gemini.text_model'),
            $this->setting('gemini.embedding_model'),
            $usage,
        );
    }

    private function ollama(Application $app, ?AiUsage $usage = null): OllamaProvider
    {
        return new OllamaProvider(
            $this->http($app),
            rtrim($this->setting('ollama.url'), '/'),
            $this->setting('ollama.text_model'),
            $this->setting('ollama.embedding_model'),
            $usage,
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
