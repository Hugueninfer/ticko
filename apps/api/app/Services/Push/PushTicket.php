<?php

declare(strict_types=1);

namespace App\Services\Push;

/**
 * Expo's answer for one message: accepted, or rejected with an error code.
 */
final readonly class PushTicket
{
    public function __construct(
        public string $to,
        public bool $ok,
        public ?string $error = null,
    ) {}

    /**
     * @param  array{status?: string, details?: array{error?: string}}  $ticket
     */
    public static function from(string $to, array $ticket): self
    {
        return new self($to, ($ticket['status'] ?? null) === 'ok', $ticket['details']['error'] ?? null);
    }

    /** The device uninstalled the app or revoked permission: stop sending to it. */
    public function deviceIsGone(): bool
    {
        return $this->error === 'DeviceNotRegistered';
    }
}
