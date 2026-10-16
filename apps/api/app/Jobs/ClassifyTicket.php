<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Classification\ApplyClassification;
use App\Enums\ClassificationStatus;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\TicketClassification;
use App\Services\Classifier\ClassifierClient;
use App\Services\Classifier\ClassifierUnavailable;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Classifies a new ticket in the background. The helpdesk never waits for
 * it: when the service is down the ticket simply stays unclassified (the
 * failure is recorded after the last attempt).
 */
final class ClassifyTicket implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [5, 30];

    public function __construct(
        public readonly string $tenantId,
        public readonly string $ticketId,
    ) {}

    public function handle(TenantContext $tenants, ClassifierClient $client, ApplyClassification $apply): void
    {
        $tenant = Tenant::find($this->tenantId);

        if ($tenant === null) {
            return;
        }

        $tenants->run($tenant, function () use ($client, $apply): void {
            $ticket = Ticket::query()->find($this->ticketId);

            if ($ticket === null) {
                return;
            }

            try {
                $result = $client->classify($ticket->subject."\n".$ticket->description);
            } catch (ClassifierUnavailable $exception) {
                if ($exception->errorCode === 'NOT_CONFIGURED' || $this->attempts() >= $this->tries) {
                    $this->fail($ticket, $exception->errorCode);

                    return;
                }

                $this->release($this->backoff[$this->attempts() - 1] ?? 30);

                return;
            }

            $apply->handle($ticket, $result);
        });
    }

    private function fail(Ticket $ticket, string $code): void
    {
        $failure = new TicketClassification(['status' => ClassificationStatus::Failed, 'error_code' => $code]);
        $failure->ticket_id = $ticket->id;
        $failure->save();
    }
}
