<?php

declare(strict_types=1);

namespace App\Actions\Classification;

use App\Enums\ClassificationStatus;
use App\Enums\FieldSource;
use App\Events\TicketClassified;
use App\Models\AuditEvent;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketClassification;
use App\Services\Classifier\Classification;
use App\Support\Telemetry\Metrics;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Stores a classifier answer and fills the ticket fields nobody set yet,
 * only when the model is confident enough. A person's choice (requester or
 * agent) is never overwritten. Deadlines follow a new priority or category.
 */
final readonly class ApplyClassification
{
    public function __construct(private Metrics $metrics) {}

    public function handle(Ticket $ticket, Classification $result): TicketClassification
    {
        $minimum = config()->float('ticko.classifier.min_confidence');

        return DB::transaction(function () use ($ticket, $result, $minimum): TicketClassification {
            $applied = [];

            if ($ticket->category_id === null && $ticket->category_source === null && $result->categoryConfidence >= $minimum) {
                $slug = config('ticko.classifier.category_slugs.'.$result->category);
                $category = is_string($slug) ? Category::query()->where('slug', $slug)->first() : null;

                if ($category !== null) {
                    $ticket->category_id = $category->id;
                    $ticket->category_source = FieldSource::Ai;
                    $applied[] = 'category';
                }
            }

            if (($ticket->priority_source === null || $ticket->priority_source === FieldSource::Ai) && $result->priorityConfidence >= $minimum && $ticket->priority !== $result->priority) {
                $ticket->priority = $result->priority;
                $ticket->priority_source = FieldSource::Ai;
                $applied[] = 'priority';
            }

            if (($ticket->sentiment_source === null || $ticket->sentiment_source === FieldSource::Ai) && $result->sentimentConfidence >= $minimum) {
                $ticket->sentiment = $result->sentiment;
                $ticket->sentiment_source = FieldSource::Ai;
                $applied[] = 'sentiment';
            }

            if (in_array('priority', $applied, true) || in_array('category', $applied, true)) {
                $ticket->recomputeDeadlines();
            }

            $ticket->save();

            $classification = new TicketClassification([
                'status' => ClassificationStatus::Classified,
                'model' => $result->model,
                'category_label' => $result->category,
                'category_confidence' => number_format($result->categoryConfidence, 3, '.', ''),
                'priority' => $result->priority,
                'priority_confidence' => number_format($result->priorityConfidence, 3, '.', ''),
                'sentiment' => $result->sentiment,
                'sentiment_confidence' => number_format($result->sentimentConfidence, 3, '.', ''),
                'applied' => $applied,
                'flags' => $result->flags,
            ]);
            $classification->ticket_id = $ticket->id;
            $classification->save();

            AuditEvent::record('ticket.classified', $ticket, null, [
                'classification_id' => $classification->id,
                'model' => $result->model,
                'labels' => ['category' => $result->category, 'priority' => $result->priority->value, 'sentiment' => $result->sentiment->value],
                'applied' => $applied,
            ]);

            TicketClassified::dispatch($ticket->tenant_id, $ticket->id);

            Log::info('Ticket classified', ['model' => $result->model, 'applied' => implode(',', $applied), 'flags' => implode(',', $result->flags)]);
            $this->metrics->classifierLabel('category', $result->category, $result->categoryConfidence, in_array('category', $applied, true));
            $this->metrics->classifierLabel('priority', $result->priority->value, $result->priorityConfidence, in_array('priority', $applied, true));
            $this->metrics->classifierLabel('sentiment', $result->sentiment->value, $result->sentimentConfidence, in_array('sentiment', $applied, true));

            return $classification;
        });
    }
}
