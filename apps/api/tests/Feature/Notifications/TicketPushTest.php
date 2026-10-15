<?php

declare(strict_types=1);

use App\Enums\PushReason;
use App\Enums\Role;
use App\Enums\TicketStatus;
use App\Jobs\SendTicketPushNotification;
use App\Models\PersonalAccessToken;
use App\Models\PushDevice;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Push\ExpoPushClient;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

const EXPO_URL = 'https://exp.host/--/api/v2/push/send';

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    $this->customer = $this->userIn($this->tenant, Role::Customer);
    $this->agent = $this->userIn($this->tenant, Role::Agent);
    $this->ticket = $this->inTenant($this->tenant, fn () => Ticket::factory()->create([
        'requester_id' => $this->customer->id,
        'subject' => 'My card number is 4111 1111 1111 1111',
    ]));

    // Expo's answer for a batch of messages; tests replace it to simulate errors.
    $this->expo = fn (Request $request) => Http::response(['data' => array_fill(0, count($request->data()), ['status' => 'ok', 'id' => 'receipt'])]);
    Http::fake([EXPO_URL => fn (Request $request) => ($this->expo)($request)]);
});

function phoneOf(User $user, Tenant $tenant, string $token, string $locale = 'pt-BR'): PushDevice
{
    return test()->inTenant($tenant, function () use ($user, $token, $locale): PushDevice {
        $accessToken = $user->createToken('phone')->accessToken;
        assert($accessToken instanceof PersonalAccessToken);

        $device = PushDevice::factory()->make(['token' => $token, 'locale' => $locale]);
        $device->user_id = $user->id;
        $device->personal_access_token_id = $accessToken->id;
        $device->save();

        return $device;
    });
}

/** @return list<array<string, mixed>> */
function sentPushes(): array
{
    return Http::recorded()
        ->filter(fn (array $pair): bool => $pair[0]->url() === EXPO_URL)
        ->flatMap(fn (array $pair): array => $pair[0]->data())
        ->values()
        ->all();
}

it('notifies the requester\'s phones when the team replies, without ticket content', function (): void {
    phoneOf($this->customer, $this->tenant, 'ExponentPushToken[phone]');
    phoneOf($this->customer, $this->tenant, 'ExponentPushToken[tablet]', 'en');
    phoneOf($this->agent, $this->tenant, 'ExponentPushToken[agent]');

    $this->actingAsUser($this->agent)
        ->postJson("/api/v1/tickets/{$this->ticket->id}/messages", ['body' => 'Your order 123 ships tomorrow'])
        ->assertCreated();

    $ref = $this->ticket->reference();
    $pushes = collect(sentPushes())->keyBy('to');

    expect($pushes->keys()->sort()->values()->all())->toBe(['ExponentPushToken[phone]', 'ExponentPushToken[tablet]'])
        ->and($pushes['ExponentPushToken[phone]']['title'])->toBe('Nova resposta')
        ->and($pushes['ExponentPushToken[phone]']['body'])->toBe("Nova resposta no ticket {$ref}")
        ->and($pushes['ExponentPushToken[tablet]']['body'])->toBe("New reply on ticket {$ref}")
        ->and((array) $pushes['ExponentPushToken[phone]']['data'])->toBe([
            'ticket_id' => $this->ticket->id,
            'url' => "ticko://tickets/{$this->ticket->id}",
        ]);

    // Neither the subject nor the reply ever leaves the server in a push.
    $raw = json_encode(sentPushes());
    expect($raw)->not->toContain('4111')->not->toContain('ships tomorrow');
});

it('sends the Expo access token when one is configured', function (): void {
    config(['services.expo.access_token' => 'expo-secret']);
    phoneOf($this->customer, $this->tenant, 'ExponentPushToken[phone]');

    $this->actingAsUser($this->agent)->postJson("/api/v1/tickets/{$this->ticket->id}/messages", ['body' => 'Hi'])->assertCreated();

    Http::assertSent(fn (Request $request): bool => $request->url() === EXPO_URL && $request->hasHeader('Authorization', 'Bearer expo-secret'));
});

it('does not push internal notes, the customer\'s own messages or tickets without phones', function (): void {
    phoneOf($this->customer, $this->tenant, 'ExponentPushToken[phone]');

    $this->actingAsUser($this->agent)
        ->postJson("/api/v1/tickets/{$this->ticket->id}/messages", ['body' => 'Note', 'is_internal' => true])
        ->assertCreated();
    $this->actingAsUser($this->customer)
        ->postJson("/api/v1/tickets/{$this->ticket->id}/messages", ['body' => 'Any news?'])
        ->assertCreated();

    expect(sentPushes())->toBe([]);
});

it('respects the requester\'s preferences', function (): void {
    phoneOf($this->customer, $this->tenant, 'ExponentPushToken[phone]');
    $this->inTenant($this->tenant, fn () => $this->customer->forceFill(['push_replies' => false])->save());

    $this->actingAsUser($this->agent)->postJson("/api/v1/tickets/{$this->ticket->id}/messages", ['body' => 'Hi'])->assertCreated();
    expect(sentPushes())->toBe([]);

    $this->patchJson("/api/v1/tickets/{$this->ticket->id}/status", ['status' => 'resolved'])->assertOk();
    expect(sentPushes())->toHaveCount(1)
        ->and(sentPushes()[0]['body'])->toBe("O ticket {$this->ticket->reference()} agora está resolvido");
});

it('pushes status changes made by the team, not by the requester', function (): void {
    phoneOf($this->customer, $this->tenant, 'ExponentPushToken[phone]', 'en');

    $this->actingAsUser($this->customer)->patchJson("/api/v1/tickets/{$this->ticket->id}/status", ['status' => 'closed'])->assertOk();
    expect(sentPushes())->toBe([]);

    $this->actingAsUser($this->agent)->patchJson("/api/v1/tickets/{$this->ticket->id}/status", ['status' => 'open'])->assertOk();
    // Setting the same status again is not a change.
    $this->patchJson("/api/v1/tickets/{$this->ticket->id}/status", ['status' => 'open'])->assertOk();

    expect(sentPushes())->toHaveCount(1)
        ->and(sentPushes()[0]['title'])->toBe('Ticket updated')
        ->and(sentPushes()[0]['body'])->toBe("Ticket {$this->ticket->reference()} is now open");
});

it('forgets phones that Expo reports as no longer registered', function (): void {
    phoneOf($this->customer, $this->tenant, 'ExponentPushToken[gone]');
    phoneOf($this->customer, $this->tenant, 'ExponentPushToken[phone]');
    $this->expo = fn (Request $request) => Http::response(['data' => array_map(
        fn (array $message): array => $message['to'] === 'ExponentPushToken[gone]'
            ? ['status' => 'error', 'message' => 'not registered', 'details' => ['error' => 'DeviceNotRegistered']]
            : ['status' => 'ok', 'id' => 'receipt'],
        $request->data(),
    )]);

    $this->actingAsUser($this->agent)->postJson("/api/v1/tickets/{$this->ticket->id}/messages", ['body' => 'Hi'])->assertCreated();

    $this->inTenant($this->tenant, fn () => expect(PushDevice::pluck('token')->all())->toBe(['ExponentPushToken[phone]']));
});

it('never touches devices of another tenant', function (): void {
    $globex = Tenant::factory()->create();
    $foreigner = $this->userIn($globex, Role::Customer);
    // Same phone registered in both tenants, Expo says it is gone.
    phoneOf($foreigner, $globex, 'ExponentPushToken[shared]');
    phoneOf($this->customer, $this->tenant, 'ExponentPushToken[shared]');
    $this->expo = fn () => Http::response(['data' => [['status' => 'error', 'details' => ['error' => 'DeviceNotRegistered']]]]);

    $this->actingAsUser($this->agent)->postJson("/api/v1/tickets/{$this->ticket->id}/messages", ['body' => 'Hi'])->assertCreated();

    expect(sentPushes())->toHaveCount(1);
    $this->inTenant($this->tenant, fn () => expect(PushDevice::count())->toBe(0));
    $this->inTenant($globex, fn () => expect(PushDevice::count())->toBe(1));
});

it('queues the push with ids only, after the response', function (): void {
    Queue::fake();

    $this->actingAsUser($this->agent)->postJson("/api/v1/tickets/{$this->ticket->id}/messages", ['body' => 'Hi'])->assertCreated();

    Queue::assertPushed(SendTicketPushNotification::class, fn (SendTicketPushNotification $job): bool => $job->tenantId === $this->tenant->id
        && $job->ticketId === $this->ticket->id
        && $job->reason === PushReason::Reply);
    Http::assertNothingSent();
});

it('skips deactivated requesters and vanished tickets', function (): void {
    phoneOf($this->customer, $this->tenant, 'ExponentPushToken[phone]');
    $this->inTenant($this->tenant, fn () => $this->customer->forceFill(['is_active' => false])->save());

    (new SendTicketPushNotification($this->tenant->id, $this->ticket->id, PushReason::Reply))->handle(app(TenantContext::class), app(ExpoPushClient::class));
    (new SendTicketPushNotification($this->tenant->id, 'missing', PushReason::Reply))->handle(app(TenantContext::class), app(ExpoPushClient::class));
    (new SendTicketPushNotification('missing', $this->ticket->id, PushReason::Reply))->handle(app(TenantContext::class), app(ExpoPushClient::class));

    expect(sentPushes())->toBe([]);
});

it('retries when Expo fails', function (): void {
    phoneOf($this->customer, $this->tenant, 'ExponentPushToken[phone]');
    $this->expo = fn () => Http::response(['errors' => [['code' => 'INTERNAL_SERVER_ERROR']]], 500);

    $job = new SendTicketPushNotification($this->tenant->id, $this->ticket->id, PushReason::Reply);

    expect(fn () => $job->handle(app(TenantContext::class), app(ExpoPushClient::class)))
        ->toThrow(RequestException::class)
        ->and($job->tries)->toBe(3);
    // The job's tenant context is restored afterwards.
    expect(app(TenantContext::class)->check())->toBeFalse();
    $this->inTenant($this->tenant, fn () => expect(Ticket::find($this->ticket->id)->status)->toBe(TicketStatus::Open));
});
