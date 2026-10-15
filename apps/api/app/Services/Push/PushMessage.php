<?php

declare(strict_types=1);

namespace App\Services\Push;

/**
 * One notification to one Expo push token. Keep title and body free of
 * ticket content: lock screens and push providers see them.
 */
final readonly class PushMessage
{
    /**
     * @param  array<string, string>  $data
     */
    public function __construct(
        public string $to,
        public string $title,
        public string $body,
        public array $data = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'to' => $this->to,
            'title' => $this->title,
            'body' => $this->body,
            'data' => (object) $this->data,
            'sound' => 'default',
            'priority' => 'high',
            'channelId' => 'tickets',
        ];
    }
}
