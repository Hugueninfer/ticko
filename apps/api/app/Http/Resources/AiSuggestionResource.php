<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\DiscardReason;
use App\Enums\SuggestionStatus;
use App\Models\AiSuggestion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Knuckles\Scribe\Attributes\ResponseField;

/**
 * @mixin AiSuggestion
 */
final class AiSuggestionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[ResponseField('status', 'string', enum: SuggestionStatus::class)]
    #[ResponseField('body', 'string', 'Draft with source markers like [1] (removed when sent).', nullable: true)]
    #[ResponseField('confidence', 'number', nullable: true)]
    #[ResponseField('error_code', 'string', 'e.g. LLM_TIMEOUT when the provider failed.', nullable: true)]
    #[ResponseField('provider', 'string', nullable: true)]
    #[ResponseField('model', 'string', nullable: true)]
    #[ResponseField('latency_ms', 'integer', nullable: true)]
    #[ResponseField('discard_reason', 'string', enum: DiscardReason::class, nullable: true)]
    #[ResponseField('reviewed_by', 'object', nullable: true)]
    #[ResponseField('reviewed_at', 'string', nullable: true)]
    #[ResponseField('message_id', 'string', nullable: true)]
    #[ResponseField('gap_reported_at', 'string', nullable: true)]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'status' => $this->status->value,
            'body' => $this->body,
            'confidence' => $this->confidence !== null ? (float) $this->confidence : null,
            'citations' => $this->citations,
            'candidates' => $this->candidates,
            'error_code' => $this->error_code,
            'provider' => $this->provider,
            'model' => $this->model,
            'latency_ms' => $this->latency_ms,
            'edited' => $this->edited,
            'discard_reason' => $this->discard_reason?->value,
            'reviewed_by' => UserSummaryResource::make($this->whenLoaded('reviewedBy')),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'message_id' => $this->message_id,
            'gap_reported_at' => $this->gap_reported_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
