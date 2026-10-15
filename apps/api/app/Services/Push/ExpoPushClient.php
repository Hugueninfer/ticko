<?php

declare(strict_types=1);

namespace App\Services\Push;

use Illuminate\Http\Client\Factory as Http;

/**
 * Minimal client for the Expo push API (https://docs.expo.dev/push-notifications/sending-notifications/).
 */
final readonly class ExpoPushClient
{
    /** Expo accepts at most 100 messages per request. */
    private const int BATCH_SIZE = 100;

    public function __construct(
        private Http $http,
        private string $url,
        private ?string $accessToken = null,
    ) {}

    /**
     * Sends the messages and returns one push ticket per message, in order.
     *
     * @param  list<PushMessage>  $messages
     * @return list<PushTicket>
     */
    public function send(array $messages): array
    {
        $tickets = [];

        foreach (array_chunk($messages, self::BATCH_SIZE) as $batch) {
            $response = $this->http
                ->acceptJson()
                ->asJson()
                ->timeout(10)
                ->retry(2, 500, throw: false)
                ->when($this->accessToken !== null && $this->accessToken !== '', fn ($request) => $request->withToken((string) $this->accessToken))
                ->post($this->url, array_map(fn (PushMessage $message): array => $message->toArray(), $batch))
                ->throw();

            /** @var list<array{status?: string, details?: array{error?: string}}> $data */
            $data = (array) $response->json('data', []);

            foreach ($batch as $index => $message) {
                $tickets[] = PushTicket::from($message->to, $data[$index] ?? []);
            }
        }

        return $tickets;
    }
}
