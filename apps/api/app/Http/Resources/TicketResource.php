<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\Role;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
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
}
