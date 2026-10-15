<?php

declare(strict_types=1);

namespace App\Services\Ai\Providers;

use App\Services\Ai\AiUnavailable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Client\PendingRequest;

/**
 * Posts JSON to an AI provider and maps failures to {@see AiUnavailable}.
 * Requests and responses are never logged: they hold ticket text and keys.
 */
final readonly class HttpJson
{
    public function __construct(private Http $http, private int $timeoutSeconds) {}

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $payload
     * @return array<mixed>
     */
    public function post(string $url, array $headers, array $payload): array
    {
        try {
            $response = $this->request()->withHeaders($headers)->post($url, $payload);
        } catch (ConnectionException $exception) {
            throw AiUnavailable::timeout($exception);
        }

        if ($response->status() === 429) {
            throw AiUnavailable::rateLimited();
        }

        if (! $response->successful()) {
            throw AiUnavailable::failed($response->status());
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw AiUnavailable::invalidResponse('The provider did not answer with JSON.');
        }

        return $json;
    }

    private function request(): PendingRequest
    {
        return $this->http->acceptJson()->asJson()->timeout($this->timeoutSeconds)->connectTimeout(5);
    }
}
