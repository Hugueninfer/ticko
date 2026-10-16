<?php

declare(strict_types=1);

namespace App\Actions\Suggestions;

use App\Actions\Tickets\PostTicketMessage;
use App\Enums\SuggestionStatus;
use App\Events\SuggestionUpdated;
use App\Models\AiSuggestion;
use App\Models\KbArticle;
use App\Models\KbChunk;
use App\Models\User;
use App\Support\Telemetry\Metrics;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The only way an AI draft reaches a customer: an agent approves it, as is
 * or edited, and it is posted as that agent's reply. Source markers ([1])
 * are for the agent and are removed from what the customer receives.
 */
final readonly class ApproveReplySuggestion
{
    public function __construct(private PostTicketMessage $post, private Metrics $metrics) {}

    public function handle(User $agent, AiSuggestion $suggestion, string $body): AiSuggestion
    {
        if ($suggestion->status !== SuggestionStatus::Ready) {
            throw ValidationException::withMessages(['suggestion' => 'Only a ready suggestion can be approved.']);
        }

        $reply = self::withoutMarkers($body);

        if ($reply === '') {
            throw ValidationException::withMessages(['body' => 'The reply is empty.']);
        }

        DB::transaction(function () use ($agent, $suggestion, $reply): void {
            $message = $this->post->handle($agent, $suggestion->ticket, $reply);

            $suggestion->forceFill([
                'status' => SuggestionStatus::Approved,
                'reviewed_by_id' => $agent->id,
                'reviewed_at' => now(),
                'message_id' => $message->id,
                'edited' => self::normalize($reply) !== self::normalize(self::withoutMarkers((string) $suggestion->body)),
            ])->save();

            // "Cited N times" on the knowledge-base screens.
            $chunkIds = array_column($suggestion->citations, 'chunk_id');
            KbChunk::query()->whereIn('id', $chunkIds)->increment('citations_count');
            KbArticle::query()->whereIn('id', array_unique(array_column($suggestion->citations, 'article_id')))->increment('citations_count');
        });

        SuggestionUpdated::dispatch($suggestion->tenant_id, $suggestion->ticket_id, $suggestion->id);
        $this->metrics->suggestionReview($suggestion->edited ? 'edited' : 'approved');

        return $suggestion;
    }

    public static function withoutMarkers(string $text): string
    {
        return trim((string) preg_replace(['/\s*\[\d{1,2}\]/u', '/[ \t]{2,}/u'], ['', ' '], $text));
    }

    private static function normalize(string $text): string
    {
        return (string) preg_replace('/\s+/u', ' ', trim($text));
    }
}
