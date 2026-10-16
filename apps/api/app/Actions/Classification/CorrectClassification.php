<?php

declare(strict_types=1);

namespace App\Actions\Classification;

use App\Enums\FieldSource;
use App\Enums\Sentiment;
use App\Enums\TicketPriority;
use App\Events\TicketClassified;
use App\Models\AuditEvent;
use App\Models\ClassificationCorrection;
use App\Models\Ticket;
use App\Models\User;
use App\Support\Telemetry\Metrics;
use Illuminate\Support\Facades\DB;

/**
 * An agent sets category, priority and/or sentiment. Fields the AI had
 * filled are recorded as corrections (AI value -> agent value); every
 * change goes to the audit trail. The agent's choice is final: the AI will
 * not change these fields again.
 */
final readonly class CorrectClassification
{
    public function __construct(private Metrics $metrics) {}

    /**
     * @param  array{category_id?: string|null, priority?: string, sentiment?: string|null}  $changes
     */
    public function handle(User $agent, Ticket $ticket, array $changes): Ticket
    {
        return DB::transaction(function () use ($agent, $ticket, $changes): Ticket {
            $latest = $ticket->latestClassification;
            $diff = [];

            if (array_key_exists('category_id', $changes) && $changes['category_id'] !== $ticket->category_id) {
                $diff['category'] = [$ticket->category_id, $changes['category_id'], $ticket->category_source];
                $ticket->category_id = $changes['category_id'];
            }

            if (isset($changes['priority']) && $changes['priority'] !== $ticket->priority->value) {
                $diff['priority'] = [$ticket->priority->value, $changes['priority'], $ticket->priority_source];
                $ticket->priority = TicketPriority::from($changes['priority']);
            }

            if (array_key_exists('sentiment', $changes) && $changes['sentiment'] !== $ticket->sentiment?->value) {
                $diff['sentiment'] = [$ticket->sentiment?->value, $changes['sentiment'], $ticket->sentiment_source];
                $ticket->sentiment = $changes['sentiment'] !== null ? Sentiment::from($changes['sentiment']) : null;
            }

            if ($diff === []) {
                return $ticket;
            }

            foreach (array_keys($diff) as $field) {
                $ticket->{$field.'_source'} = FieldSource::Staff;
            }

            if (isset($diff['priority']) || isset($diff['category'])) {
                $ticket->recomputeDeadlines();
            }

            $ticket->save();

            foreach ($diff as $field => [$from, $to, $source]) {
                $this->metrics->classificationCorrection($field, $source->value ?? 'nobody');

                if ($source !== FieldSource::Ai) {
                    continue;
                }

                $correction = new ClassificationCorrection(['field' => $field, 'ai_value' => $from, 'corrected_value' => $to]);
                $correction->ticket_id = $ticket->id;
                $correction->classification_id = $latest?->id;
                $correction->user_id = $agent->id;
                $correction->save();
            }

            AuditEvent::record('ticket.classification_changed', $ticket, $agent, [
                'changes' => array_map(fn (array $d): array => ['from' => $d[0], 'to' => $d[1], 'was_set_by' => $d[2]?->value], $diff),
            ]);

            TicketClassified::dispatch($ticket->tenant_id, $ticket->id);

            return $ticket;
        });
    }
}
