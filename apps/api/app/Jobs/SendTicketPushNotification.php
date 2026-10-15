<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\PushReason;
use App\Models\PushDevice;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Services\Push\ExpoPushClient;
use App\Services\Push\PushMessage;
use App\Services\Push\PushTicket;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Tells the requester of a ticket, on every phone they signed in with, that
 * the team replied or changed the status. The notification only carries
 * the ticket reference; the app loads the rest through the API.
 *
 * Holds ids only: models cannot be restored before the tenant (and RLS) is set.
 */
final class SendTicketPushNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60];

    public function __construct(
        public readonly string $tenantId,
        public readonly string $ticketId,
        public readonly PushReason $reason,
    ) {}

    public function handle(TenantContext $tenants, ExpoPushClient $expo): void
    {
        $tenant = Tenant::find($this->tenantId);

        if ($tenant === null) {
            return;
        }

        $tenants->run($tenant, fn () => $this->send($expo));
    }

    private function send(ExpoPushClient $expo): void
    {
        $ticket = Ticket::query()->with('requester.pushDevices')->find($this->ticketId);
        $requester = $ticket?->requester;

        if ($ticket === null || $requester === null || ! $requester->is_active || ! $this->wanted($requester->push_replies, $requester->push_status_changes)) {
            return;
        }

        $messages = array_values($requester->pushDevices
            ->map(fn (PushDevice $device): PushMessage => $this->message($ticket, $device))
            ->all());

        if ($messages === []) {
            return;
        }

        $gone = array_values(array_map(
            fn (PushTicket $result): string => $result->to,
            array_filter($expo->send($messages), fn (PushTicket $result): bool => $result->deviceIsGone()),
        ));

        if ($gone !== []) {
            PushDevice::query()->whereIn('token', $gone)->delete();
        }
    }

    private function wanted(bool $replies, bool $statusChanges): bool
    {
        return match ($this->reason) {
            PushReason::Reply => $replies,
            PushReason::StatusChange => $statusChanges,
        };
    }

    private function message(Ticket $ticket, PushDevice $device): PushMessage
    {
        $locale = str_replace('-', '_', $device->locale);
        $key = $this->reason->value;

        return new PushMessage(
            to: $device->token,
            title: __("push.{$key}.title", [], $locale),
            body: __("push.{$key}.body", [
                'ref' => $ticket->reference(),
                'status' => __("push.statuses.{$ticket->status->value}", [], $locale),
            ], $locale),
            data: ['ticket_id' => $ticket->id, 'url' => "ticko://tickets/{$ticket->id}"],
        );
    }
}
