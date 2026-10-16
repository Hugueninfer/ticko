<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\ClassificationStatus;
use App\Enums\FieldSource;
use App\Enums\Role;
use App\Enums\Sentiment;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use Knuckles\Scribe\Attributes\ResponseField;

/**
 * @mixin Ticket
 */
final class TicketResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[ResponseField('status', 'string', enum: TicketStatus::class)]
    #[ResponseField('priority', 'string', enum: TicketPriority::class)]
    #[ResponseField('category', 'object', nullable: true)]
    #[ResponseField('assignee', 'object', nullable: true)]
    #[ResponseField('requester.role', 'string', enum: Role::class)]
    #[ResponseField('requester.email', 'string', 'Only shown to staff.', nullable: true)]
    #[ResponseField('assignee.role', 'string', enum: Role::class)]
    #[ResponseField('assignee.email', 'string', 'Only shown to staff.', nullable: true)]
    #[ResponseField('category.description', 'string', nullable: true)]
    #[ResponseField('category.tickets_count', 'integer', required: false)]
    #[ResponseField('last_message', 'object', 'Latest public message, as an excerpt (ticket listing only).', required: false, nullable: true)]
    #[ResponseField('last_message.author_role', 'string', enum: Role::class)]
    #[ResponseField('sentiment', 'string', 'Customer sentiment (staff only).', required: false, nullable: true, enum: Sentiment::class)]
    #[ResponseField('sources', 'object', 'Who set category, priority and sentiment: requester, ai or staff (staff only).', required: false)]
    #[ResponseField('sources.category', 'string', nullable: true, enum: FieldSource::class)]
    #[ResponseField('sources.priority', 'string', nullable: true, enum: FieldSource::class)]
    #[ResponseField('sources.sentiment', 'string', nullable: true, enum: FieldSource::class)]
    #[ResponseField('classification', 'object', 'Latest automatic classification (staff, single-ticket responses).', required: false, nullable: true)]
    #[ResponseField('classification.status', 'string', enum: ClassificationStatus::class)]
    #[ResponseField('classification.model', 'string', nullable: true)]
    #[ResponseField('classification.category.label', 'string', 'Classifier category (closed set; mapped to the tenant category by slug).', enum: ['entrega', 'pagamento', 'troca_devolucao', 'conta_acesso', 'produto', 'outro'])]
    #[ResponseField('classification.priority.label', 'string', enum: TicketPriority::class)]
    #[ResponseField('classification.sentiment.label', 'string', enum: Sentiment::class)]
    #[ResponseField('classification.error_code', 'string', nullable: true)]
    #[ResponseField('classification.category', 'object', nullable: true)]
    #[ResponseField('classification.priority', 'object', nullable: true)]
    #[ResponseField('classification.sentiment', 'object', nullable: true)]
    #[ResponseField('resolved_at', 'string', nullable: true)]
    #[ResponseField('closed_at', 'string', nullable: true)]
    #[ResponseField('sla.first_response_at', 'string', nullable: true)]
    #[ResponseField('sla.first_response_due_at', 'string', nullable: true)]
    #[ResponseField('sla.resolution_due_at', 'string', nullable: true)]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'description' => $this->description,
            'status' => $this->status->value,
            'priority' => $this->priority->value,
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'requester' => UserSummaryResource::make($this->whenLoaded('requester')),
            'assignee' => UserSummaryResource::make($this->whenLoaded('assignee')),
            'last_message' => $this->whenLoaded('lastPublicMessage', fn (): ?array => $this->lastPublicMessage === null ? null : [
                'id' => $this->lastPublicMessage->id,
                'excerpt' => Str::limit(Str::squish($this->lastPublicMessage->body), 140),
                'author_name' => $this->lastPublicMessage->author->name,
                'author_role' => $this->lastPublicMessage->author->role->value,
                'created_at' => $this->lastPublicMessage->created_at->toIso8601String(),
            ]),
            'sentiment' => $this->when($this->forStaff($request), fn () => $this->sentiment?->value),
            'sources' => $this->when($this->forStaff($request), fn (): array => [
                'category' => $this->category_source?->value,
                'priority' => $this->priority_source?->value,
                'sentiment' => $this->sentiment_source?->value,
            ]),
            'classification' => $this->when(
                $this->forStaff($request) && $this->relationLoaded('latestClassification'),
                fn (): ?array => $this->classification(),
            ),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'sla' => [
                'first_response_at' => $this->first_response_at?->toIso8601String(),
                'first_response_due_at' => $this->first_response_due_at?->toIso8601String(),
                'resolution_due_at' => $this->resolution_due_at?->toIso8601String(),
                'is_overdue' => $this->isOverdue(),
            ],
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }

    private function forStaff(Request $request): bool
    {
        $user = $request->user();

        return $user instanceof User && $user->isStaff();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function classification(): ?array
    {
        $c = $this->latestClassification;

        if ($c === null) {
            return null;
        }

        $label = fn (?string $value, ?string $confidence): ?array => $value === null ? null : ['label' => $value, 'confidence' => (float) $confidence];

        return [
            'status' => $c->status->value,
            'model' => $c->model,
            'category' => $label($c->category_label, $c->category_confidence),
            'priority' => $label($c->priority?->value, $c->priority_confidence),
            'sentiment' => $label($c->sentiment?->value, $c->sentiment_confidence),
            'applied' => $c->applied,
            'error_code' => $c->error_code,
            'created_at' => $c->created_at->toIso8601String(),
        ];
    }
}
