<?php

declare(strict_types=1);

use App\Actions\Classification\ApplyClassification;
use App\Enums\FieldSource;
use App\Enums\Role;
use App\Enums\Sentiment;
use App\Enums\TicketPriority;
use App\Models\AuditEvent;
use App\Models\Category;
use App\Models\ClassificationCorrection;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Services\Classifier\Classification;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    $this->agent = $this->userIn($this->tenant, Role::Agent);
    $this->customer = $this->userIn($this->tenant, Role::Customer);
    [$this->delivery, $this->payment] = $this->inTenant($this->tenant, fn () => [
        Category::factory()->create(['slug' => 'entrega']),
        Category::factory()->create(['slug' => 'pagamento']),
    ]);
    $this->ticket = $this->inTenant($this->tenant, function (): Ticket {
        $ticket = Ticket::factory()->create(['requester_id' => $this->customer->id, 'priority' => TicketPriority::Normal]);
        app(ApplyClassification::class)->handle($ticket, new Classification('m1', 'entrega', 0.9, TicketPriority::Urgent, 0.9, Sentiment::Negative, 0.9, []));

        return $ticket->fresh();
    });
});

it('shows the AI classification to staff only', function (): void {
    $this->actingAsUser($this->agent)->getJson("/api/v1/tickets/{$this->ticket->id}")
        ->assertOk()
        ->assertJsonPath('data.sentiment', 'negative')
        ->assertJsonPath('data.sources.priority', 'ai')
        ->assertJsonPath('data.classification.model', 'm1')
        ->assertJsonPath('data.classification.category.label', 'entrega')
        ->assertJsonPath('data.classification.applied', ['category', 'priority', 'sentiment']);

    $this->actingAsUser($this->customer)->getJson("/api/v1/tickets/{$this->ticket->id}")
        ->assertOk()
        ->assertJsonMissingPath('data.sentiment')
        ->assertJsonMissingPath('data.sources')
        ->assertJsonMissingPath('data.classification');
});

it('records corrections of AI values and makes them final', function (): void {
    $this->actingAsUser($this->agent)->patchJson("/api/v1/tickets/{$this->ticket->id}/classification", [
        'category_id' => $this->payment->id,
        'priority' => 'high',
        'sentiment' => 'neutral',
    ])->assertOk()
        ->assertJsonPath('data.category.id', $this->payment->id)
        ->assertJsonPath('data.priority', 'high')
        ->assertJsonPath('data.sources', ['category' => 'staff', 'priority' => 'staff', 'sentiment' => 'staff']);

    $this->inTenant($this->tenant, function (): void {
        $corrections = ClassificationCorrection::query()->orderBy('field')->get(['field', 'ai_value', 'corrected_value', 'user_id']);
        expect($corrections->map(fn ($c) => [$c->field, $c->ai_value, $c->corrected_value])->all())->toBe([
            ['category', $this->delivery->id, $this->payment->id],
            ['priority', 'urgent', 'high'],
            ['sentiment', 'negative', 'neutral'],
        ])->and($corrections->pluck('user_id')->unique()->all())->toBe([$this->agent->id]);

        $audit = AuditEvent::query()->where('action', 'ticket.classification_changed')->sole();
        expect($audit->actor_id)->toBe($this->agent->id)->and($audit->data['changes']['priority'])->toEqual(['from' => 'urgent', 'to' => 'high', 'was_set_by' => 'ai']);

        // The classifier never changes the agent's choice again.
        $ticket = Ticket::find($this->ticket->id);
        app(ApplyClassification::class)->handle($ticket, new Classification('m1', 'entrega', 0.99, TicketPriority::Low, 0.99, Sentiment::Positive, 0.99, []));
        $ticket->refresh();
        expect($ticket->category_id)->toBe($this->payment->id)->and($ticket->priority)->toBe(TicketPriority::High)->and($ticket->sentiment)->toBe(Sentiment::Neutral)
            ->and($ticket->priority_source)->toBe(FieldSource::Staff);
    });
});

it('does not count changes to values a person set as AI corrections', function (): void {
    $this->actingAsUser($this->agent)->patchJson("/api/v1/tickets/{$this->ticket->id}/classification", ['priority' => 'low'])->assertOk();
    $this->patchJson("/api/v1/tickets/{$this->ticket->id}/classification", ['priority' => 'high'])->assertOk();
    // No change at all is a no-op.
    $this->patchJson("/api/v1/tickets/{$this->ticket->id}/classification", ['priority' => 'high'])->assertOk();

    $this->inTenant($this->tenant, fn () => expect(ClassificationCorrection::count())->toBe(1)
        ->and(AuditEvent::query()->where('action', 'ticket.classification_changed')->count())->toBe(2));
});

it('is for staff only and validates the values', function (): void {
    $foreign = Tenant::factory()->create();
    $foreignCategory = $this->inTenant($foreign, fn () => Category::factory()->create());

    $this->actingAsUser($this->customer)->patchJson("/api/v1/tickets/{$this->ticket->id}/classification", ['priority' => 'urgent'])->assertForbidden();

    $this->actingAsUser($this->agent);
    $this->patchJson("/api/v1/tickets/{$this->ticket->id}/classification", ['priority' => 'whenever'])->assertUnprocessable()->assertJsonValidationErrors('priority');
    $this->patchJson("/api/v1/tickets/{$this->ticket->id}/classification", ['sentiment' => 'angry'])->assertUnprocessable()->assertJsonValidationErrors('sentiment');
    $this->patchJson("/api/v1/tickets/{$this->ticket->id}/classification", ['category_id' => $foreignCategory->id])->assertUnprocessable()->assertJsonValidationErrors('category_id');
});
