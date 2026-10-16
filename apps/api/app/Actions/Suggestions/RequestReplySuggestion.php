<?php

declare(strict_types=1);

namespace App\Actions\Suggestions;

use App\Events\SuggestionUpdated;
use App\Jobs\GenerateReplySuggestion;
use App\Models\AiSuggestion;
use App\Models\Ticket;
use App\Models\User;

/** An agent asks for a draft; it is written in the background. */
final readonly class RequestReplySuggestion
{
    public function handle(User $agent, Ticket $ticket): AiSuggestion
    {
        $suggestion = new AiSuggestion;
        $suggestion->ticket_id = $ticket->id;
        $suggestion->requested_by_id = $agent->id;
        $suggestion->save();

        GenerateReplySuggestion::dispatch($suggestion->tenant_id, $suggestion->id)->afterCommit();
        SuggestionUpdated::dispatch($suggestion->tenant_id, $ticket->id, $suggestion->id);

        return $suggestion;
    }
}
