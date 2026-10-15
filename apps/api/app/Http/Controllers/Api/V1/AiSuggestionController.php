<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Suggestions\ApproveReplySuggestion;
use App\Actions\Suggestions\DiscardReplySuggestion;
use App\Actions\Suggestions\ReportKnowledgeGap;
use App\Actions\Suggestions\RequestReplySuggestion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Suggestions\ApproveSuggestionRequest;
use App\Http\Requests\Suggestions\DiscardSuggestionRequest;
use App\Http\Resources\AiSuggestionResource;
use App\Models\AiSuggestion;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;

#[Group('AI suggestions', 'Reply drafts written by the AI from the knowledge base, for agents. The AI never answers a customer by itself: a draft is only sent when an agent approves it.')]
final class AiSuggestionController extends Controller
{
    /**
     * Latest suggestion of a ticket
     *
     * `data` is null when none was requested yet.
     */
    #[ResponseFromApiResource(AiSuggestionResource::class, AiSuggestion::class, with: ['reviewedBy'])]
    public function latest(Ticket $ticket): JsonResponse
    {
        Gate::authorize('viewSuggestions', $ticket);

        $suggestion = AiSuggestion::query()->with('reviewedBy')->where('ticket_id', $ticket->id)->latest()->orderByDesc('id')->first();

        return response()->json(['data' => $suggestion !== null ? AiSuggestionResource::make($suggestion)->resolve() : null]);
    }

    /**
     * Ask for a suggestion
     *
     * The draft is written in the background; follow it with the latest
     * suggestion endpoint or the staff realtime channel.
     */
    #[ResponseFromApiResource(AiSuggestionResource::class, AiSuggestion::class, 202, with: ['reviewedBy'])]
    public function store(Request $request, Ticket $ticket, RequestReplySuggestion $requestSuggestion): JsonResponse
    {
        Gate::authorize('suggestReply', $ticket);

        $suggestion = $requestSuggestion->handle($this->currentUser($request), $ticket);

        return AiSuggestionResource::make($suggestion->load('reviewedBy'))->response()->setStatusCode(202);
    }

    /**
     * Approve and send
     *
     * Posts the (possibly edited) draft as the agent's public reply.
     */
    #[ResponseFromApiResource(AiSuggestionResource::class, AiSuggestion::class, with: ['reviewedBy'])]
    public function approve(ApproveSuggestionRequest $request, AiSuggestion $suggestion, ApproveReplySuggestion $approve): AiSuggestionResource
    {
        Gate::authorize('review', $suggestion);

        $approve->handle($this->currentUser($request), $suggestion, $request->string('body')->toString());

        return AiSuggestionResource::make($suggestion->load('reviewedBy'));
    }

    /**
     * Discard
     */
    #[ResponseFromApiResource(AiSuggestionResource::class, AiSuggestion::class, with: ['reviewedBy'])]
    public function discard(DiscardSuggestionRequest $request, AiSuggestion $suggestion, DiscardReplySuggestion $discard): AiSuggestionResource
    {
        Gate::authorize('review', $suggestion);

        $discard->handle($this->currentUser($request), $suggestion, $request->reason());

        return AiSuggestionResource::make($suggestion->load('reviewedBy'));
    }

    /**
     * Report a knowledge gap
     *
     * The knowledge base had no answer for this ticket.
     */
    #[ResponseFromApiResource(AiSuggestionResource::class, AiSuggestion::class, with: ['reviewedBy'])]
    public function gap(AiSuggestion $suggestion, ReportKnowledgeGap $report): AiSuggestionResource
    {
        Gate::authorize('review', $suggestion);

        return AiSuggestionResource::make($report->handle($suggestion)->load('reviewedBy'));
    }
}
