<?php

declare(strict_types=1);

namespace App\Actions\Suggestions;

use App\Events\SuggestionUpdated;
use App\Models\AiSuggestion;

/** Marks that the knowledge base had no answer for this ticket (listed on the knowledge-base screen). */
final readonly class ReportKnowledgeGap
{
    public function handle(AiSuggestion $suggestion): AiSuggestion
    {
        $suggestion->gap_reported_at ??= now();
        $suggestion->save();

        SuggestionUpdated::dispatch($suggestion->tenant_id, $suggestion->ticket_id, $suggestion->id);

        return $suggestion;
    }
}
