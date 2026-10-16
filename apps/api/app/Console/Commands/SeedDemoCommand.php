<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Classification\ApplyClassification;
use App\Actions\Classification\CorrectClassification;
use App\Actions\Knowledge\PublishKnowledgeArticle;
use App\Actions\Knowledge\SaveKnowledgeArticle;
use App\Actions\Suggestions\ApproveReplySuggestion;
use App\Actions\Suggestions\DiscardReplySuggestion;
use App\Actions\Suggestions\RequestReplySuggestion;
use App\Enums\DiscardReason;
use App\Enums\FieldSource;
use App\Enums\Role;
use App\Enums\Sentiment;
use App\Enums\SuggestionStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Jobs\ClassifyTicket;
use App\Models\AiSuggestion;
use App\Models\Category;
use App\Models\KbArticle;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\Classifier\Classification;
use App\Support\Demo\DemoKnowledgeBase;
use App\Support\Demo\DemoTickets;
use App\Support\Tenancy\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use LogicException;

/**
 * Seeds the demo tenant "acme" (Loja Aurora): staff and customers, categories,
 * the knowledge base, ~25 tickets in every state, AI suggestions in every
 * outcome, classifications and agents' corrections.
 *
 * Everything goes through the real actions (publishing indexes articles,
 * suggestions run the real job with the configured provider, reviews and
 * corrections are recorded and audited), so the screens, the audit trail
 * and the dashboards tell one coherent story.
 *
 * Idempotent: running it again only adds what is missing. --fresh deletes the
 * demo tenant first (never in production).
 */
final class SeedDemoCommand extends Command
{
    private const string SLUG = 'acme';

    /** Labels of the deterministic stand-in classification (model "demo-seed"). */
    private const array CATEGORY_LABELS = [
        'Entrega' => 'entrega', 'Pagamento' => 'pagamento', 'Troca e devolução' => 'troca_devolucao',
        'Conta e acesso' => 'conta_acesso', 'Produto' => 'produto', 'Outro' => 'outro',
    ];

    protected $signature = 'ticko:demo {--fresh : Delete the demo tenant and seed it again}';

    protected $description = 'Create (or complete) the demo tenant "acme" and print the credentials of new users';

    /** @var array<string, User> */
    private array $people = [];

    /** @var list<array{string, string, string}> */
    private array $credentials = [];

    public function handle(TenantContext $tenants): int
    {
        if ($this->laravel->isProduction()) {
            $this->error('Refusing to seed demo data in production.');

            return self::FAILURE;
        }

        $this->people = $this->credentials = [];

        if ($this->option('fresh')) {
            Tenant::query()->where('slug', self::SLUG)->delete();
            $this->warn('Deleted the demo tenant "acme".');
        }

        // Indexing, suggestions and broadcasts run inline, so the data is complete when this ends.
        config(['queue.default' => 'sync']);

        $tenant = Tenant::query()->firstOrCreate(['slug' => self::SLUG], ['name' => 'Loja Aurora']);
        $counts = $tenants->run($tenant, fn (): array => $this->seed($tenant));

        $this->info('Demo tenant ready. Send "X-Tenant: acme" when logging in.');
        $this->table(['Added', 'Count'], array_map(fn (string $what, int $n): array => [$what, (string) $n], array_keys($counts), $counts));

        $credentials = $this->newCredentials();

        if ($credentials !== []) {
            $this->table(['Role', 'E-mail', 'Password'], $credentials);
        } else {
            $this->line('No new users (passwords are only shown when a user is created).');
        }

        return self::SUCCESS;
    }

    /**
     * @return list<array{string, string, string}>
     */
    private function newCredentials(): array
    {
        return $this->credentials;
    }

    /**
     * @return array<string, int>
     */
    private function seed(Tenant $tenant): array
    {
        $this->people($tenant);
        $categories = $this->categories();
        $articles = $this->knowledgeBase();

        $added = ['tickets' => 0, 'messages' => 0, 'suggestions' => 0, 'classifications' => 0, 'corrections' => 0];
        $tickets = [];

        foreach (DemoTickets::all() as $spec) {
            $ticket = Ticket::query()->where('subject', $spec['subject'])->first();

            if ($ticket === null) {
                $ticket = $this->ticket($spec, $categories);
                $added['tickets']++;
                $added['messages'] += count($spec['messages']);
            }

            $tickets[] = [$ticket, $spec];
        }

        foreach ($tickets as [$ticket, $spec]) {
            if ($ticket->latestClassification()->doesntExist()) {
                $this->classify($ticket, $spec);
                $added['classifications']++;
            }
        }

        foreach ($tickets as [$ticket, $spec]) {
            if ($spec['correct'] !== [] && $ticket->category_source !== FieldSource::Staff
                && $ticket->priority_source !== FieldSource::Staff && $ticket->sentiment_source !== FieldSource::Staff) {
                $this->correct($ticket->refresh(), $spec['correct'], $categories);
                $added['corrections']++;
            }

            // A suggestion that failed for a temporary reason (rate limit, overload) is tried again.
            $done = AiSuggestion::query()->where('ticket_id', $ticket->id)
                ->when($spec['suggestion'] !== 'failed', fn ($q) => $q->where('status', '!=', SuggestionStatus::Failed->value));

            if ($spec['suggestion'] !== null && $done->doesntExist()) {
                $this->suggestion($ticket->refresh(), $spec['suggestion']);
                $added['suggestions']++;
            }
        }

        // With the classifier running (make up-ml), the real model classifies the new tickets
        // too, through the queue (only the queue reaches it); people's choices are kept.
        if (config()->string('ticko.classifier.url') !== '' && $added['tickets'] > 0) {
            foreach ($tickets as [$ticket]) {
                ClassifyTicket::dispatch($tenant->id, $ticket->id)->onConnection('redis');
            }
        }

        return ['articles' => $articles] + $added;
    }

    private function people(Tenant $tenant): void
    {
        $everyone = [];

        foreach (DemoTickets::STAFF as $key => [$name, $email, $role]) {
            $everyone[$key] = [$name, $email, Role::from($role)];
        }

        foreach (DemoTickets::CUSTOMERS as $key => [$name, $email]) {
            $everyone[$key] = [$name, $email, Role::Customer];
        }

        foreach ($everyone as $key => [$name, $email, $role]) {
            $user = User::query()->where('email', $email)->first();

            if ($user === null) {
                $password = Str::password(20);
                $user = User::create(['name' => $name, 'email' => $email, 'password' => $password, 'role' => $role]);
                // Only the accounts used to explore the demo are listed.
                if (in_array($key, ['admin', 'agent', 'customer'], true)) {
                    $this->credentials[] = [$role->value, $email, $password];
                }
            }

            $this->people[$key] = $user;
        }
    }

    /**
     * @return array<string, Category>
     */
    private function categories(): array
    {
        $categories = [];

        foreach (DemoTickets::CATEGORIES as $name => $description) {
            $categories[$name] = Category::query()->firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'description' => $description]);
        }

        return $categories;
    }

    /**
     * Demo articles, published through the normal flow (indexed inline).
     * Returns how many were created; none if the tenant already has articles.
     */
    private function knowledgeBase(): int
    {
        if (KbArticle::query()->exists()) {
            return 0;
        }

        $author = $this->people['admin'];
        $categories = Category::query()->pluck('id', 'name');
        $save = app(SaveKnowledgeArticle::class);
        $publish = app(PublishKnowledgeArticle::class);

        foreach (DemoKnowledgeBase::articles() as [$title, $category, $visibility, $published, $body]) {
            $categoryId = $category !== null ? ($categories[$category] ?? null) : null;
            $article = $save->handle($author, [
                'title' => $title,
                'body' => $body,
                'visibility' => $visibility->value,
                'tags' => [],
                'category_id' => is_string($categoryId) ? $categoryId : null,
            ]);

            if ($published) {
                $publish->handle($author, $article);
            }
        }

        return count(DemoKnowledgeBase::articles());
    }

    /**
     * @param  array{subject: string, description: string, requester: string, category: string|null, priority: TicketPriority, status: TicketStatus, hours: int, assignee: string|null, messages: list<array{string, string, int, bool}>, suggestion: string|null, correct: array<string, string|null>}  $spec
     * @param  array<string, Category>  $categories
     */
    private function ticket(array $spec, array $categories): Ticket
    {
        $opened = now()->subHours($spec['hours']);
        $ticket = new Ticket([
            'subject' => $spec['subject'],
            'description' => $spec['description'],
            'priority' => $spec['priority'],
            'category_id' => $spec['category'] === null ? null : $categories[$spec['category']]->id,
        ]);
        // What the customer picked; the classifier never overwrites it.
        $ticket->category_source = $spec['category'] === null ? null : FieldSource::Requester;
        $ticket->priority_source = $spec['category'] === null ? null : FieldSource::Requester;
        $ticket->requester_id = $this->people[$spec['requester']]->id;
        $ticket->assignee_id = $spec['assignee'] === null ? null : $this->people[$spec['assignee']]->id;
        $ticket->created_at = $opened;
        $ticket->updated_at = $opened;
        $ticket->save();

        $last = $opened;

        foreach ($spec['messages'] as [$author, $body, $minutes, $internal]) {
            $message = new TicketMessage(['body' => $body, 'is_internal' => $internal]);
            $message->ticket_id = $ticket->id;
            $message->author_id = $this->people[$author]->id;
            $message->created_at = $last = $opened->copy()->addMinutes($minutes);
            $message->save();

            if (! $internal && $this->people[$author]->isStaff()) {
                $ticket->first_response_at ??= $message->created_at;
            }
        }

        $ticket->status = $spec['status'];
        $ticket->resolved_at = in_array($spec['status'], [TicketStatus::Resolved, TicketStatus::Closed], true) ? $last->copy()->addMinutes(5) : null;
        $ticket->closed_at = $spec['status'] === TicketStatus::Closed ? $last->copy()->addHours(2) : null;
        $ticket->updated_at = $last;
        $ticket->save();

        return $ticket;
    }

    /**
     * Deterministic stand-in for the classifier (model "demo-seed"), applied through
     * the real action: same rules, audit trail and metrics as the service's answers.
     *
     * @param  array{subject: string, description: string, category: string|null, priority: TicketPriority}  $spec
     */
    private function classify(Ticket $ticket, array $spec): void
    {
        $text = mb_strtolower($spec['subject'].' '.$spec['description']);
        $category = $spec['category'] !== null ? self::CATEGORY_LABELS[$spec['category']] : match (true) {
            Str::contains($text, ['rastre', 'entrega', 'endereço']) => 'entrega',
            Str::contains($text, ['boleto', 'parcel', 'cobran', 'pagamento']) => 'pagamento',
            Str::contains($text, ['trocar', 'troca', 'zíper', 'tamanho']) => 'troca_devolucao',
            Str::contains($text, ['senha', 'cpf', 'conta', 'código']) => 'conta_acesso',
            Str::contains($text, ['loja física', 'calça', 'tamanho m']) => 'produto',
            default => 'outro',
        };
        $sentiment = match (true) {
            Str::contains($text, ['chateada', 'irritado', 'preocupada', 'quebrado', 'amassada', 'agora', 'terceira']) => Sentiment::Negative,
            Str::contains($text, ['adorei', 'obrigada', 'elogio', 'parabéns', 'perfeita']) => Sentiment::Positive,
            default => Sentiment::Neutral,
        };
        $priority = $sentiment === Sentiment::Negative && $spec['priority'] === TicketPriority::Normal ? TicketPriority::High : $spec['priority'];
        // Stable confidences: category and sentiment above the 0.6 threshold; priority
        // between 0.45 and 0.97, so some suggestions are kept without being applied.
        $confidence = fn (string $salt, float $from): float => round($from + (crc32($salt.$spec['subject']) % (int) ((0.98 - $from) * 100)) / 100, 2);

        app(ApplyClassification::class)->handle($ticket, new Classification(
            'demo-seed', $category, $confidence('c', 0.62), $priority, $confidence('p', 0.45), $sentiment, $confidence('s', 0.62), [],
        ));
    }

    /**
     * @param  array<string, string|null>  $changes  category (name), priority, sentiment
     * @param  array<string, Category>  $categories
     */
    private function correct(Ticket $ticket, array $changes, array $categories): void
    {
        $input = [];

        if (array_key_exists('category', $changes)) {
            $input['category_id'] = $changes['category'] === null ? null : $categories[$changes['category']]->id;
        }

        if (isset($changes['priority'])) {
            $input['priority'] = $changes['priority'];
        }

        if (array_key_exists('sentiment', $changes)) {
            $input['sentiment'] = $changes['sentiment'];
        }

        app(CorrectClassification::class)->handle($this->people['agent'], $ticket, $input);
    }

    /**
     * Real providers' free tiers refuse bursts: pause between requests and try
     * once more after a temporary failure. The offline fake needs neither.
     */
    private function requestSuggestion(User $agent, Ticket $ticket): AiSuggestion
    {
        $pause = config()->string('ticko.ai.text_provider') === 'fake' ? 0 : config()->integer('ticko.demo.ai_pause_seconds');
        $suggestion = null;

        foreach ([1, 2] as $attempt) {
            if ($pause > 0) {
                sleep($attempt === 1 ? $pause : 4 * $pause);
            }

            $suggestion = app(RequestReplySuggestion::class)->handle($agent, $ticket)->refresh();

            if ($suggestion->status !== SuggestionStatus::Failed) {
                break;
            }
        }

        return $suggestion ?? throw new LogicException('No suggestion requested.');
    }

    private function suggestion(Ticket $ticket, string $outcome): void
    {
        $agent = $ticket->assignee_id !== null ? User::query()->findOrFail($ticket->assignee_id) : $this->people['agent'];

        if ($outcome === 'failed') {
            // A provider timeout, as the job records it (no call is made for the demo).
            $suggestion = new AiSuggestion;
            $suggestion->ticket_id = $ticket->id;
            $suggestion->requested_by_id = $agent->id;
            $suggestion->forceFill(['status' => SuggestionStatus::Failed, 'error_code' => 'LLM_TIMEOUT', 'confidence' => '0.000', 'latency_ms' => 25_000])->save();

            return;
        }

        // The real job, with the configured provider (the offline fake by default).
        $suggestion = $this->requestSuggestion($agent, $ticket);

        if ($suggestion->status !== SuggestionStatus::Ready) {
            return;
        }

        $body = (string) $suggestion->body;
        $first = explode(' ', $ticket->requester->name)[0];

        match (true) {
            $outcome === 'approve' => app(ApproveReplySuggestion::class)->handle($agent, $suggestion, $body),
            $outcome === 'edit' => app(ApproveReplySuggestion::class)->handle($agent, $suggestion, "Oi, {$first}! {$body}\n\nQualquer dúvida, é só responder aqui."),
            str_starts_with($outcome, 'discard:') => app(DiscardReplySuggestion::class)->handle($agent, $suggestion, DiscardReason::from(substr($outcome, 8))),
            default => null, // "review": ready, waiting for an agent
        };
    }
}
