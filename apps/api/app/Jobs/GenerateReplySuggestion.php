<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\SuggestionStatus;
use App\Events\SuggestionUpdated;
use App\Models\AiSuggestion;
use App\Models\Tenant;
use App\Models\TicketMessage;
use App\Services\Ai\AiUnavailable;
use App\Services\Ai\TextGenerator;
use App\Services\Knowledge\KnowledgeSearch;
use App\Services\Knowledge\SearchHit;
use App\Services\Knowledge\SearchOptions;
use App\Services\Suggestions\ReplyDrafter;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Drafts a reply for an agent from the knowledge base:
 *
 *  1. searches the customer-visible articles with the customer's words;
 *  2. if nothing scores above the confidence floor, says it does not know
 *     (no model call, no invented answer) and lists the closest articles;
 *  3. otherwise asks the model for a cited answer and validates it.
 *
 * It never sends anything: an agent approves, edits or discards the draft.
 */
final class GenerateReplySuggestion implements ShouldQueue
{
    use Queueable;

    /** Retries are left to the agent ("try again"): each attempt costs quota. */
    public int $tries = 1;

    public int $timeout = 90;

    public function __construct(
        public readonly string $tenantId,
        public readonly string $suggestionId,
    ) {}

    public function handle(TenantContext $tenants, KnowledgeSearch $search, ReplyDrafter $drafter, TextGenerator $generator): void
    {
        $tenant = Tenant::find($this->tenantId);

        if ($tenant !== null) {
            $tenants->run($tenant, fn () => $this->generate($tenant, $search, $drafter, $generator));
        }
    }

    private function generate(Tenant $tenant, KnowledgeSearch $search, ReplyDrafter $drafter, TextGenerator $generator): void
    {
        $suggestion = AiSuggestion::query()->with('ticket.requester')->find($this->suggestionId);

        if ($suggestion === null || $suggestion->status !== SuggestionStatus::Pending) {
            return;
        }

        $ticket = $suggestion->ticket;
        $started = hrtime(true);
        $minimum = config()->float('ticko.ai.min_confidence');
        $customerMessages = array_values(array_map(fn (mixed $body): string => is_string($body) ? $body : '', TicketMessage::query()
            ->where('ticket_id', $ticket->id)
            ->where('author_id', $ticket->requester_id)
            ->where('is_internal', false)
            ->latest()
            ->orderByDesc('id')
            ->limit(3)
            ->pluck('body')
            ->reverse()
            ->all()));
        $question = trim($ticket->subject."\n".$ticket->description."\n".implode("\n", $customerMessages));

        $hits = [];

        try {
            $hits = $search->search($question, new SearchOptions(
                textWeight: config()->float('ticko.ai.text_weight'),
                topK: config()->integer('ticko.ai.top_k'),
                customerVisibleOnly: true,
            ));

            if ($hits === [] || $hits[0]->score < $minimum) {
                $this->finish($suggestion, SuggestionStatus::Insufficient, $hits, $started, confidence: $hits[0]->score ?? 0.0);

                return;
            }

            $draft = $drafter->draft($ticket, $tenant->name, $this->firstName($ticket->requester->name), $customerMessages, $hits);
        } catch (AiUnavailable $exception) {
            $this->finish($suggestion, SuggestionStatus::Failed, $hits, $started, error: $exception->errorCode);

            return;
        }

        if (! $draft->answerable || $draft->confidence < $minimum) {
            $this->finish($suggestion, SuggestionStatus::Insufficient, $hits, $started, confidence: $draft->confidence, generator: $generator);

            return;
        }

        $suggestion->body = $draft->answer;
        $suggestion->citations = array_map(fn (int $n): array => $this->citation($n, $hits[$n - 1]), $draft->cited);
        $this->finish($suggestion, SuggestionStatus::Ready, $hits, $started, confidence: $draft->confidence, generator: $generator);
    }

    /**
     * @param  list<SearchHit>  $hits
     */
    private function finish(
        AiSuggestion $suggestion,
        SuggestionStatus $status,
        array $hits,
        int $started,
        float $confidence = 0.0,
        ?string $error = null,
        ?TextGenerator $generator = null,
    ): void {
        $suggestion->forceFill([
            'status' => $status,
            'confidence' => number_format(max(0.0, min(1.0, $confidence)), 3, '.', ''),
            'candidates' => $this->candidates($hits),
            'error_code' => $error,
            'provider' => $generator?->provider(),
            'model' => $generator?->model(),
            'latency_ms' => (int) ((hrtime(true) - $started) / 1_000_000),
        ])->save();

        SuggestionUpdated::dispatch($suggestion->tenant_id, $suggestion->ticket_id, $suggestion->id);
    }

    /**
     * @return array{n: int, chunk_id: string, article_id: string, title: string, heading: string, quote: string, score: float}
     */
    private function citation(int $n, SearchHit $hit): array
    {
        return [
            'n' => $n,
            'chunk_id' => $hit->chunkId,
            'article_id' => $hit->articleId,
            'title' => $hit->title,
            'heading' => $hit->heading,
            'quote' => mb_substr($hit->content, 0, 400),
            'score' => round($hit->score, 3),
        ];
    }

    /**
     * The closest articles (one entry per article), shown when there is no draft.
     *
     * @param  list<SearchHit>  $hits
     * @return list<array{article_id: string, title: string, score: float}>
     */
    private function candidates(array $hits): array
    {
        $articles = [];

        foreach ($hits as $hit) {
            $articles[$hit->articleId] ??= ['article_id' => $hit->articleId, 'title' => $hit->title, 'score' => round($hit->score, 3)];
        }

        return array_values(array_slice($articles, 0, 3));
    }

    private function firstName(string $name): string
    {
        return explode(' ', trim($name))[0] ?? '';
    }
}
