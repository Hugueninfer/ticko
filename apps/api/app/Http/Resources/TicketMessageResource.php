<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\Role;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Knuckles\Scribe\Attributes\ResponseField;

/**
 * @mixin TicketMessage
 */
final class TicketMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[ResponseField('author.role', 'string', enum: Role::class)]
    #[ResponseField('author.email', 'string', 'Only shown to staff.', nullable: true)]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'body' => $this->body,
            'is_internal' => $this->is_internal,
            'author' => UserSummaryResource::make($this->whenLoaded('author')),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
