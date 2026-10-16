<?php

declare(strict_types=1);

use App\Actions\Classification\ApplyClassification;
use App\Enums\ClassificationStatus;
use App\Enums\FieldSource;
use App\Enums\Role;
use App\Enums\Sentiment;
use App\Enums\TicketPriority;
use App\Jobs\ClassifyTicket;
use App\Models\AuditEvent;
use App\Models\Category;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\TicketClassification;
use App\Services\Classifier\ClassifierClient;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

const CLASSIFIER = 'http://classifier.test:8000/v1/classify';

beforeEach(function (): void {
    config(['ticko.classifier.url' => 'http://classifier.test:8000', 'ticko.classifier.token' => str_repeat('k', 40)]);
    $this->tenant = Tenant::factory()->create();
    $this->customer = $this->userIn($this->tenant, Role::Customer);
    $this->delivery = $this->inTenant($this->tenant, fn () => Category::factory()->create(['name' => 'Entrega', 'slug' => 'entrega']));
    $this->answer = fn (array $overrides = []) => array_replace_recursive([
        'model' => 'bertimbau-lora-v1',
        'category' => ['label' => 'entrega', 'confidence' => 0.93],
        'priority' => ['label' => 'urgent', 'confidence' => 0.81],
        'sentiment' => ['label' => 'negative', 'confidence' => 0.88],
        'flags' => ['pii_masked'],
    ], $overrides);
    $this->reply = fn () => Http::response(($this->answer)());
    Http::fake([CLASSIFIER => fn () => ($this->reply)()]);
});

function openTicket(object $test, array $payload = []): Ticket
{
    $id = $test->actingAsUser($test->customer)->postJson('/api/v1/tickets', [
        'subject' => 'Pedido atrasado',
        'description' => 'Meu pedido não chegou. CPF 123.456.789-09, fone (11) 98765-4321, email ana@example.com.',
        ...$payload,
    ])->assertCreated()->json('data.id');

    return test()->inTenant($test->tenant, fn () => Ticket::with('latestClassification')->findOrFail($id));
}

it('classifies new tickets and fills the fields nobody set', function (): void {
    $before = now();
    $ticket = openTicket($this);

    expect($ticket->category_id)->toBe($this->delivery->id)
        ->and($ticket->category_source)->toBe(FieldSource::Ai)
        ->and($ticket->priority)->toBe(TicketPriority::Urgent)
        ->and($ticket->priority_source)->toBe(FieldSource::Ai)
        ->and($ticket->sentiment)->toBe(Sentiment::Negative)
        ->and($ticket->latestClassification->applied)->toBe(['category', 'priority', 'sentiment'])
        ->and($ticket->latestClassification->model)->toBe('bertimbau-lora-v1')
        // Urgent SLA: 15 minutes for the first response.
        ->and($ticket->first_response_due_at->diffInMinutes($ticket->created_at, true))->toEqual(15.0)
        ->and($ticket->created_at->gte($before->subSecond()))->toBeTrue();

    $this->inTenant($this->tenant, function () use ($ticket): void {
        $event = AuditEvent::sole();
        expect($event->action)->toBe('ticket.classified')
            ->and($event->actor_id)->toBeNull()
            ->and($event->subject_id)->toBe($ticket->id)
            ->and($event->data['labels'])->toBe(['category' => 'entrega', 'priority' => 'urgent', 'sentiment' => 'negative']);
    });
});

it('sends only masked text, with the service token', function (): void {
    openTicket($this);

    Http::assertSent(function (Request $request): bool {
        expect($request->url())->toBe(CLASSIFIER)
            ->and($request->header('Authorization'))->toBe(['Bearer '.str_repeat('k', 40)])
            ->and($request['text'])->toContain('[CPF]')->toContain('[TELEFONE]')->toContain('[EMAIL]')
            ->and($request['text'])->not->toContain('123.456')->not->toContain('ana@example.com')->not->toContain('98765');

        return true;
    });
});

it('never overwrites what the requester chose, and stores low-confidence labels without applying them', function (): void {
    $this->answer = fn () => [
        'model' => 'm1', 'flags' => [],
        'category' => ['label' => 'entrega', 'confidence' => 0.95],
        'priority' => ['label' => 'urgent', 'confidence' => 0.95],
        'sentiment' => ['label' => 'positive', 'confidence' => 0.41],
    ];
    $other = $this->inTenant($this->tenant, fn () => Category::factory()->create(['slug' => 'outra']));

    $ticket = openTicket($this, ['category_id' => $other->id, 'priority' => 'low']);

    expect($ticket->category_id)->toBe($other->id)
        ->and($ticket->category_source)->toBe(FieldSource::Requester)
        ->and($ticket->priority)->toBe(TicketPriority::Low)
        ->and($ticket->sentiment)->toBeNull()
        ->and($ticket->latestClassification->applied)->toBe([])
        ->and($ticket->latestClassification->sentiment)->toBe(Sentiment::Positive);
});

it('leaves the category empty when the tenant has no matching category', function (): void {
    $this->answer = fn () => [
        'model' => 'm1', 'flags' => [],
        'category' => ['label' => 'produto', 'confidence' => 0.99],
        'priority' => ['label' => 'normal', 'confidence' => 0.9],
        'sentiment' => ['label' => 'neutral', 'confidence' => 0.9],
    ];

    $ticket = openTicket($this);

    expect($ticket->category_id)->toBeNull()->and($ticket->latestClassification->category_label)->toBe('produto');
});

it('rejects untrusted answers from the service', function (array $answer): void {
    $this->answer = fn () => $answer;
    $ticket = $this->inTenant($this->tenant, fn () => Ticket::factory()->create(['requester_id' => $this->customer->id]));
    $job = (new ClassifyTicket($this->tenant->id, $ticket->id))->withFakeQueueInteractions();

    $job->handle(app(TenantContext::class), app(ClassifierClient::class), app(ApplyClassification::class));

    $job->assertReleased();
    $this->inTenant($this->tenant, fn () => expect(TicketClassification::count())->toBe(0)->and($ticket->fresh()->priority_source)->toBeNull());
})->with([
    'unknown label' => [['model' => 'm', 'flags' => [], 'category' => ['label' => 'admin', 'confidence' => 0.9], 'priority' => ['label' => 'urgent', 'confidence' => 0.9], 'sentiment' => ['label' => 'neutral', 'confidence' => 0.9]]],
    'markup in a label' => [['model' => 'm', 'flags' => [], 'category' => ['label' => '<script>x</script>', 'confidence' => 0.9], 'priority' => ['label' => 'urgent', 'confidence' => 0.9], 'sentiment' => ['label' => 'neutral', 'confidence' => 0.9]]],
    'confidence above 1' => [['model' => 'm', 'flags' => [], 'category' => ['label' => 'entrega', 'confidence' => 7], 'priority' => ['label' => 'urgent', 'confidence' => 0.9], 'sentiment' => ['label' => 'neutral', 'confidence' => 0.9]]],
    'missing task' => [['model' => 'm', 'flags' => [], 'category' => ['label' => 'entrega', 'confidence' => 0.9]]],
    'bad model name' => [['model' => str_repeat('x', 200), 'flags' => [], 'category' => ['label' => 'entrega', 'confidence' => 0.9], 'priority' => ['label' => 'urgent', 'confidence' => 0.9], 'sentiment' => ['label' => 'neutral', 'confidence' => 0.9]]],
    'not JSON' => [['oops']],
]);

it('retries when the service is down and records the failure after the last attempt', function (): void {
    $this->reply = fn () => Http::response('', 503);
    $ticket = $this->inTenant($this->tenant, fn () => Ticket::factory()->create(['requester_id' => $this->customer->id]));

    $first = (new ClassifyTicket($this->tenant->id, $ticket->id))->withFakeQueueInteractions();
    $first->handle(app(TenantContext::class), app(ClassifierClient::class), app(ApplyClassification::class));
    $first->assertReleased(delay: 5);

    $last = (new ClassifyTicket($this->tenant->id, $ticket->id))->withFakeQueueInteractions();
    $last->job->attempts = 3;
    $last->handle(app(TenantContext::class), app(ClassifierClient::class), app(ApplyClassification::class));
    $last->assertNotReleased();

    $this->inTenant($this->tenant, function (): void {
        $failure = TicketClassification::sole();
        expect($failure->status)->toBe(ClassificationStatus::Failed)->and($failure->error_code)->toBe('HTTP_503');
    });
});

it('does nothing when classification is turned off', function (): void {
    config(['ticko.classifier.url' => '']);
    Queue::fake();

    openTicket($this);

    Queue::assertNotPushed(ClassifyTicket::class);
    Http::assertNothingSent();
});

it('ignores tickets and tenants that no longer exist', function (): void {
    (new ClassifyTicket($this->tenant->id, '01k7a0000000000000000tk999'))->handle(app(TenantContext::class), app(ClassifierClient::class), app(ApplyClassification::class));
    (new ClassifyTicket('01k7a0000000000000000te999', 'x'))->handle(app(TenantContext::class), app(ClassifierClient::class), app(ApplyClassification::class));

    Http::assertNothingSent();
});

it('keeps the audit trail append-only', function (): void {
    openTicket($this);

    $this->inTenant($this->tenant, function (): void {
        // In a savepoint, so the failed statement does not abort the test's transaction.
        expect(fn () => DB::transaction(fn () => AuditEvent::query()->update(['action' => 'tampered'])))->toThrow(QueryException::class);
        expect(AuditEvent::sole()->action)->toBe('ticket.classified');
    });
});
