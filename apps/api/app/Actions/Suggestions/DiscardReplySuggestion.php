<?php

declare(strict_types=1);

namespace App\Actions\Suggestions;

use App\Enums\DiscardReason;
use App\Enums\SuggestionStatus;
use App\Events\SuggestionUpdated;
use App\Models\AiSuggestion;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final readonly class DiscardReplySuggestion
{
    public function handle(User $agent, AiSuggestion $suggestion, DiscardReason $reason): AiSuggestion
    {
        if ($suggestion->status->isReviewed()) {
            throw ValidationException::withMessages(['suggestion' => 'This suggestion was already reviewed.']);
        }

        $suggestion->forceFill([
            'status' => SuggestionStatus::Discarded,
            'discard_reason' => $reason,
            'reviewed_by_id' => $agent->id,
            'reviewed_at' => now(),
        ])->save();

        SuggestionUpdated::dispatch($suggestion->tenant_id, $suggestion->ticket_id, $suggestion->id);

        return $suggestion;
    }
}
