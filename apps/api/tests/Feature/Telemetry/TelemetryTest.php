<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Enums\TicketPriority;
use App\Models\AiSuggestion;
use App\Models\Category;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Support\Telemetry\TraceJobs;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use Tests\Support\FakeTelemetry;
use Tests\Support\Knowledge;

beforeEach(function (): void {
    $this->telemetry = FakeTelemetry::install();
    $this->spa = ['Origin' => 'http://spa.test', 'Referer' => 'http://spa.test/'];
    $this->tenant = Tenant::factory()->create(['slug' => 'aurora', 'name' => 'Loja Aurora']);
    $this->agent = $this->userIn($this->tenant, Role::Agent, ['email' => 'ana.agente@example.com']);
    $this->customer = $this->userIn($this->tenant, Role::Customer, ['name' => 'Mariana Costa', 'email' => 'mariana@example.com']);
});

it('traces requests by route template, with no URL, query, headers or body', function (): void {
    $ticket = $this->inTenant($this->tenant, fn () => Ticket::factory()->create(['requester_id' => $this->customer->id]));

    $this->actingAsUser($this->agent)->getJson("/api/v1/tickets/{$ticket->id}?secret=abc123")->assertOk();

    $span = $this->telemetry->span('GET /api/v1/tickets/{ticket}');
    expect($span->getKind())->toBe(SpanKind::KIND_SERVER)
        ->and($span->getAttributes()->toArray())->toBe([
            'http.request.method' => 'GET',
            'http.route' => '/api/v1/tickets/{ticket}',
            'http.response.status_code' => 200,
            'ticko.tenant' => 'aurora',
        ])
        ->and($this->telemetry->total('ticko.http.server.duration', ['http.route' => '/api/v1/tickets/{ticket}', 'http.response.status_code' => 200]))->toBe(1)
        ->and($this->telemetry->dump())->not->toContain($ticket->id)->not->toContain('abc123')->not->toContain('Bearer');
});

it('counts failed sign-ins per tenant without recording who tried', function (): void {
    $this->withHeaders([...$this->spa, 'X-Tenant' => 'aurora'])
        ->postJson('/api/v1/auth/login', ['email' => 'ana.agente@example.com', 'password' => 'S3nha-errada-123'])
        ->assertUnprocessable();

    expect($this->telemetry->total('ticko.auth.login.failures', ['tenant' => 'aurora']))->toBe(1)
        ->and($this->telemetry->span('POST /api/v1/auth/login')->getAttributes()->get('http.response.status_code'))->toBe(422)
        ->and($this->telemetry->dump())->not->toContain('ana.agente')->not->toContain('S3nha-errada-123');
});

it('marks server errors on the span without leaking the exception message', function (): void {
    Route::middleware('api')->get('/api/v1/_boom', fn () => throw new RuntimeException('SQL: select * from users where email = ana@example.com'));

    $this->getJson('/api/v1/_boom')->assertStatus(500);

    $span = $this->telemetry->span('GET /api/v1/_boom');
    expect($span->getStatus()->getCode())->toBe(StatusCode::STATUS_ERROR)
        ->and($this->telemetry->dump())->not->toContain('ana@example.com');
});

it('continues the request trace in queued jobs and only to our own classifier', function (): void {
    config(['ticko.classifier.url' => 'http://classifier:8000', 'ticko.classifier.token' => str_repeat('k', 40)]);
    Http::fake(['http://classifier:8000/*' => Http::response([
        'model' => 'lora-v1', 'flags' => [],
        'category' => ['label' => 'entrega', 'confidence' => 0.9],
        'priority' => ['label' => 'high', 'confidence' => 0.8],
        'sentiment' => ['label' => 'negative', 'confidence' => 0.9],
    ])]);
    $this->inTenant($this->tenant, fn () => Category::factory()->create(['slug' => 'entrega']));

    $this->actingAsUser($this->customer)->postJson('/api/v1/tickets', [
        'subject' => 'Pedido atrasado',
        'description' => 'Meu CPF é 123.456.789-09 e o pedido não chegou.',
    ])->assertCreated();

    $request = $this->telemetry->span('POST /api/v1/tickets');
    $job = $this->telemetry->span('job ClassifyTicket');
    $call = $this->telemetry->span('POST classifier');

    expect($job->getTraceId())->toBe($request->getTraceId())
        ->and($job->getKind())->toBe(SpanKind::KIND_CONSUMER)
        ->and($call->getParentSpanId())->toBe($job->getSpanId())
        ->and($call->getAttributes()->toArray())->toMatchArray(['server.address' => 'classifier', 'http.response.status_code' => 200])
        ->and($this->telemetry->total('ticko.queue.job.duration', ['job_name' => 'ClassifyTicket', 'outcome' => 'processed']))->toBe(1)
        ->and($this->telemetry->total('ticko.classifier.duration', ['outcome' => 'ok']))->toBe(1)
        ->and($this->telemetry->total('ticko.classifier.confidence', ['task' => 'priority', 'label' => 'high', 'applied' => 'yes']))->toBe(1)
        ->and($this->telemetry->dump())->not->toContain('123.456.789-09')->not->toContain('Pedido atrasado');

    Http::assertSent(fn (Request $sent): bool => str_starts_with($sent->header('traceparent')[0] ?? '', '00-'.$request->getTraceId()));
});

it('puts the trace context in job payloads', function (): void {
    $span = $this->telemetry->telemetry->tracer()->spanBuilder('parent')->startSpan();
    $scope = $span->activate();
    $payload = app(TraceJobs::class)->payload();
    $scope->detach();
    $span->end();

    expect($payload['telemetry']['traceparent'] ?? '')->toStartWith('00-'.$span->getContext()->getTraceId());
});

it('never sends the trace context to third parties', function (): void {
    config(['ticko.ai.text_provider' => 'gemini', 'ticko.ai.gemini.key' => 'AIzaSyD-fake-key-0000000000000000000000']);
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
        'candidates' => [['content' => ['parts' => [['text' => '{"answer":"ok"}']]]]],
        'usageMetadata' => ['promptTokenCount' => 1200, 'candidatesTokenCount' => 80],
    ])]);

    $this->inTenant($this->tenant, fn () => app(App\Services\Ai\TextGenerator::class)->generate(new App\Services\Ai\TextPrompt('regras', 'tarefa')));

    Http::assertSent(fn (Request $sent): bool => $sent->header('traceparent') === []);
    $call = $this->telemetry->span('POST generativelanguage.googleapis.com');
    expect($call->getAttributes()->toArray())->not->toHaveKey('url.full')
        ->and($this->telemetry->span('chat gemini-3.7-flash')->getAttributes()->toArray())->toMatchArray([
            'gen_ai.system' => 'gemini', 'gen_ai.usage.input_tokens' => 1200, 'gen_ai.usage.output_tokens' => 80,
        ])
        ->and($this->telemetry->total('ticko.ai.tokens', ['tenant' => 'aurora', 'type' => 'input', 'provider' => 'gemini']))->toBe(1200)
        // 1200 × 0.30 + 80 × 2.50 per million tokens.
        ->and(round($this->telemetry->total('ticko.ai.cost.usd', ['tenant' => 'aurora']), 6))->toBe(0.00056)
        ->and($this->telemetry->dump())->not->toContain('AIza')->not->toContain('regras')->not->toContain('tarefa');
});

it('meters suggestions, their tokens and cost, and the agents\' reviews', function (): void {
    $article = Knowledge::publish($this->tenant, $this->agent, 'Política de entregas e prazos', Knowledge::DELIVERY);
    $ticket = $this->inTenant($this->tenant, fn () => Ticket::factory()->create([
        'requester_id' => $this->customer->id,
        'subject' => 'Pedido não chegou e o rastreio parou',
        'description' => 'O rastreamento não é atualizado há 6 dias. O que faço?',
    ]));

    $id = $this->actingAsUser($this->agent)->postJson("/api/v1/tickets/{$ticket->id}/suggestions")->assertAccepted()->json('data.id');
    $this->postJson("/api/v1/suggestions/{$id}/discard", ['reason' => 'tone'])->assertOk();

    $suggestion = $this->inTenant($this->tenant, fn () => AiSuggestion::findOrFail($id));
    expect($suggestion->input_tokens)->toBeGreaterThan(0)
        ->and($suggestion->output_tokens)->toBeGreaterThan(0)
        ->and((float) $suggestion->cost_usd)->toBeGreaterThan(0)
        ->and($this->telemetry->total('ticko.ai.suggestions', ['tenant' => 'aurora', 'outcome' => 'ready']))->toBe(1)
        ->and($this->telemetry->total('ticko.ai.suggestion.reviews', ['tenant' => 'aurora', 'result' => 'discarded', 'reason' => 'tone']))->toBe(1)
        ->and($this->telemetry->total('ticko.ai.llm.duration', ['provider' => 'fake', 'outcome' => 'ok']))->toBe(1)
        ->and($this->telemetry->dump())->not->toContain('rastreamento')->not->toContain('Mariana')->not->toContain($article->id);
});

it('records SLA and queue gauges per tenant every minute', function (): void {
    $this->inTenant($this->tenant, function (): void {
        Ticket::factory()->create(['requester_id' => $this->customer->id, 'priority' => TicketPriority::Urgent]);
        $late = Ticket::factory()->create(['requester_id' => $this->customer->id, 'priority' => TicketPriority::High]);
        $late->forceFill(['first_response_due_at' => now()->subHour(), 'resolution_due_at' => now()->subMinute()])->save();
    });

    $this->artisan('ticko:telemetry:snapshot')->assertSuccessful();

    expect($this->telemetry->total('ticko.tickets.open', ['tenant' => 'aurora', 'priority' => 'urgent']))->toBe(1)
        ->and($this->telemetry->total('ticko.tickets.open', ['tenant' => 'aurora', 'priority' => 'high']))->toBe(1)
        ->and($this->telemetry->total('ticko.tickets.overdue', ['tenant' => 'aurora', 'deadline' => 'first_response']))->toBe(1)
        ->and($this->telemetry->total('ticko.tickets.overdue', ['tenant' => 'aurora', 'deadline' => 'resolution']))->toBe(1);
});

it('keeps metric cardinality bounded: no user, ticket or free-text attributes', function (): void {
    $ticket = $this->inTenant($this->tenant, fn () => Ticket::factory()->create(['requester_id' => $this->customer->id]));
    $this->actingAsUser($this->agent)->getJson("/api/v1/tickets/{$ticket->id}")->assertOk();
    $this->getJson('/api/v1/tickets?search=mariana')->assertOk();
    $this->withHeaders([...$this->spa, 'X-Tenant' => 'aurora'])->postJson('/api/v1/auth/login', ['email' => 'x@example.com', 'password' => 'nope-nope-nope'])->assertUnprocessable();
    $this->artisan('ticko:telemetry:snapshot')->assertSuccessful();

    $allowed = ['http.request.method', 'http.route', 'http.response.status_code', 'tenant', 'job_name', 'outcome', 'provider', 'model', 'type',
        'result', 'reason', 'task', 'label', 'applied', 'field', 'set_by', 'priority', 'deadline', 'queue'];

    foreach ($this->telemetry->points() as $point) {
        expect(array_diff(array_keys($point['attributes']), $allowed))->toBe([], $point['name']);

        foreach ($point['attributes'] as $value) {
            // No ids (ULIDs) and no free text in any attribute value.
            expect((string) $value)->not->toMatch('/[0-9A-HJKMNP-TV-Z]{26}/i')->not->toContain('mariana');
        }
    }
});

it('sends logs with the trace id and without secrets or personal data', function (): void {
    $span = $this->telemetry->telemetry->tracer()->spanBuilder('work')->startSpan();
    $scope = $span->activate();

    Log::warning('Login of ana@example.com with Bearer 12|abcdefghijklmnopqrstuvwxyz0123 failed', [
        'password' => 'hunter2-hunter2',
        'body' => 'Meu cartão 4111 1111 1111 1111 foi cobrado duas vezes',
        'cpf' => '123.456.789-09',
        'note' => 'ligue para (11) 98765-4321',
        'attempt' => 3,
        'exception' => new RuntimeException('SQL: insert into ticket_messages (body) values (Olá, sou a Mariana)'),
    ]);

    $scope->detach();
    $span->end();

    $log = collect($this->telemetry->logs())->first(fn ($record) => str_contains((string) $record->getBody(), 'Login of'));
    expect($log)->not->toBeNull()
        ->and($log->getSpanContext()?->getTraceId())->toBe($span->getContext()->getTraceId())
        ->and($log->getBody())->toBe('Login of [EMAIL] with Bearer [REDACTED] failed')
        ->and($log->getAttributes()->toArray())->toMatchArray([
            'ticko.password' => '[REDACTED]',
            'ticko.body' => '[REDACTED]',
            'ticko.cpf' => '[REDACTED]',
            'ticko.note' => 'ligue para [TELEFONE]',
            'ticko.attempt' => 3,
            'ticko.exception.type' => RuntimeException::class,
        ])
        ->and($this->telemetry->dump())->not->toContain('hunter2')->not->toContain('4111')->not->toContain('Mariana')->not->toContain('abcdefghijklmnop');
});
