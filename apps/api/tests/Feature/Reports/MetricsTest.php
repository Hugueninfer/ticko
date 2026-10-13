<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Category;
use App\Models\Tenant;
use App\Models\Ticket;
use Illuminate\Support\Carbon;

beforeEach(function (): void {
    $this->travelTo(Carbon::parse('2026-10-12 12:00:00'));

    $this->tenant = Tenant::factory()->create();
    $this->admin = $this->userIn($this->tenant, Role::Admin);
    $this->customer = $this->userIn($this->tenant, Role::Customer);
});

/**
 * Creates a ticket opened at $opened (urgent: 15 min to first response), answered after $answerMinutes.
 */
function ticketAt(object $test, Tenant $tenant, string $requesterId, string $opened, ?int $answerMinutes, ?string $categoryId = null): Ticket
{
    return $test->inTenant($tenant, function () use ($requesterId, $opened, $answerMinutes, $categoryId): Ticket {
        $at = Carbon::parse($opened);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requesterId,
            'priority' => 'urgent',
            'category_id' => $categoryId,
            'created_at' => $at,
            'updated_at' => $at,
        ]);

        if ($answerMinutes !== null) {
            $ticket->forceFill(['first_response_at' => $at->copy()->addMinutes($answerMinutes)])->save();
        }

        return $ticket;
    });
}

it('computes volume, median first response and SLA compliance for the period', function (): void {
    $category = $this->inTenant($this->tenant, fn () => Category::factory()->create(['name' => 'Delivery']));

    ticketAt($this, $this->tenant, $this->customer->id, '2026-10-10 09:00', 10, $category->id);
    ticketAt($this, $this->tenant, $this->customer->id, '2026-10-11 09:00', 20, $category->id);
    ticketAt($this, $this->tenant, $this->customer->id, '2026-10-11 10:00', 30);
    ticketAt($this, $this->tenant, $this->customer->id, '2026-10-12 11:50', null); // not due yet: excluded from SLA
    ticketAt($this, $this->tenant, $this->customer->id, '2026-10-12 08:00', null); // missed
    ticketAt($this, $this->tenant, $this->customer->id, '2026-10-08 09:00', 5);    // previous period

    $response = $this->actingAsUser($this->admin)
        ->getJson('/api/v1/metrics?from=2026-10-10&to=2026-10-12')
        ->assertOk()
        ->assertJsonPath('data.period', ['from' => '2026-10-10', 'to' => '2026-10-12'])
        ->assertJsonPath('data.tickets_received', ['value' => 5, 'previous' => 1])
        ->assertJsonPath('data.volume_by_day', [
            ['date' => '2026-10-10', 'count' => 1],
            ['date' => '2026-10-11', 'count' => 2],
            ['date' => '2026-10-12', 'count' => 2],
        ]);

    expect($response->json('data.first_response_median_minutes'))->toEqual(['value' => 20, 'previous' => 5])
        // 1 of 4 settled deadlines met (10 min); 20 and 30 min replies and the unanswered one missed.
        ->and($response->json('data.sla_first_response_percent'))->toEqual(['value' => 25, 'previous' => 100])
        ->and($response->json('data.by_category'))->toEqual([
            ['category_id' => null, 'name' => null, 'count' => 3],
            ['category_id' => $category->id, 'name' => 'Delivery', 'count' => 2],
        ]);
});

it('defaults to the last 30 days and handles an empty tenant', function (): void {
    $this->actingAsUser($this->admin)->getJson('/api/v1/metrics')
        ->assertOk()
        ->assertJsonPath('data.period', ['from' => '2026-09-13', 'to' => '2026-10-12'])
        ->assertJsonPath('data.tickets_received', ['value' => 0, 'previous' => 0])
        ->assertJsonPath('data.first_response_median_minutes', ['value' => null, 'previous' => null])
        ->assertJsonPath('data.sla_first_response_percent', ['value' => null, 'previous' => null])
        ->assertJsonCount(30, 'data.volume_by_day');
});

it('never counts another tenant\'s tickets', function (): void {
    $other = Tenant::factory()->create();
    $otherCustomer = $this->userIn($other, Role::Customer);
    $otherCategory = $this->inTenant($other, fn () => Category::factory()->create());
    ticketAt($this, $other, $otherCustomer->id, '2026-10-11 09:00', 5, $otherCategory->id);
    ticketAt($this, $this->tenant, $this->customer->id, '2026-10-11 09:00', 50);

    $response = $this->actingAsUser($this->admin)->getJson('/api/v1/metrics?from=2026-10-11&to=2026-10-11')->assertOk();

    expect($response->json('data.tickets_received.value'))->toBe(1)
        ->and($response->json('data.first_response_median_minutes.value'))->toEqual(50)
        ->and($response->json('data.sla_first_response_percent.value'))->toEqual(0)
        ->and($response->json('data.by_category'))->toEqual([['category_id' => null, 'name' => null, 'count' => 1]]);
});

it('is only available to admins', function (Role $role): void {
    $this->actingAsUser($this->userIn($this->tenant, $role))
        ->getJson('/api/v1/metrics')
        ->assertForbidden();
})->with([Role::Customer, Role::Agent]);

it('validates the period', function (string $query, string $field): void {
    $this->actingAsUser($this->admin)
        ->getJson('/api/v1/metrics?'.$query)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'bad format' => ['from=12/10/2026', 'from'],
    'end before start' => ['from=2026-10-10&to=2026-10-01', 'to'],
    'future end' => ['to=2026-12-01', 'to'],
]);

it('clamps the period to one year', function (): void {
    $this->actingAsUser($this->admin)
        ->getJson('/api/v1/metrics?from=2020-01-01&to=2026-10-12')
        ->assertOk()
        ->assertJsonPath('data.period.from', '2025-10-12')
        ->assertJsonCount(366, 'data.volume_by_day');
});
