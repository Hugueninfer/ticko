<?php

declare(strict_types=1);

use App\Enums\PushPlatform;
use App\Enums\Role;
use App\Models\PersonalAccessToken;
use App\Models\PushDevice;
use App\Models\Tenant;
use Laravel\Sanctum\Sanctum;

const EXPO_TOKEN = 'ExponentPushToken[abcdefghijklmnopqrstuv]';

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create(['slug' => 'acme']);
    $this->customer = $this->userIn($this->tenant, Role::Customer);
});

function devicePayload(array $overrides = []): array
{
    return ['token' => EXPO_TOKEN, 'platform' => 'android', 'locale' => 'pt-BR', ...$overrides];
}

function devicesOf(Tenant $tenant): Illuminate\Support\Collection
{
    return test()->inTenant($tenant, fn () => PushDevice::query()->orderBy('created_at')->get());
}

it('registers the device against the current API token', function (): void {
    $this->actingAsUser($this->customer)->postJson('/api/v1/push-devices', devicePayload())->assertNoContent();

    $devices = devicesOf($this->tenant);
    expect($devices)->toHaveCount(1)
        ->and($devices[0]->user_id)->toBe($this->customer->id)
        ->and($devices[0]->token)->toBe(EXPO_TOKEN)
        ->and($devices[0]->platform)->toBe(PushPlatform::Android)
        ->and($devices[0]->locale)->toBe('pt-BR')
        ->and($devices[0]->personal_access_token_id)->toBe(PersonalAccessToken::query()->sole()->id);
});

it('is idempotent and moves a phone to the account that signed in last', function (): void {
    $this->actingAsUser($this->customer)->postJson('/api/v1/push-devices', devicePayload())->assertNoContent();
    $this->postJson('/api/v1/push-devices', devicePayload(['locale' => 'en']))->assertNoContent();

    $other = $this->userIn($this->tenant, Role::Customer);
    $this->actingAsUser($other)->postJson('/api/v1/push-devices', devicePayload(['platform' => 'ios']))->assertNoContent();

    $devices = devicesOf($this->tenant);
    expect($devices)->toHaveCount(1)
        ->and($devices[0]->user_id)->toBe($other->id)
        ->and($devices[0]->platform)->toBe(PushPlatform::Ios);
});

it('validates the device', function (array $overrides, string $field): void {
    $this->actingAsUser($this->customer)
        ->postJson('/api/v1/push-devices', devicePayload($overrides))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'missing token' => [['token' => null], 'token'],
    'not an Expo token' => [['token' => 'fcm:abc'], 'token'],
    'token with extra characters' => [['token' => 'ExponentPushToken[abc] ; drop'], 'token'],
    'unknown platform' => [['platform' => 'windows'], 'platform'],
    'unsupported locale' => [['locale' => 'fr'], 'locale'],
]);

it('only accepts API tokens, not browser sessions', function (): void {
    Sanctum::actingAs($this->customer);

    $this->withHeader('X-Tenant', 'acme')->postJson('/api/v1/push-devices', devicePayload())->assertStatus(400);

    expect(devicesOf($this->tenant))->toBeEmpty();
});

it('requires authentication', function (): void {
    $this->postJson('/api/v1/push-devices', devicePayload())->assertUnauthorized();
    $this->deleteJson('/api/v1/push-devices/current')->assertUnauthorized();
});

it('unregisters only the device of the current token', function (): void {
    $this->actingAsUser($this->customer)->postJson('/api/v1/push-devices', devicePayload(['token' => 'ExponentPushToken[tablet]']))->assertNoContent();
    $this->actingAsUser($this->customer)->postJson('/api/v1/push-devices', devicePayload())->assertNoContent();

    $this->deleteJson('/api/v1/push-devices/current')->assertNoContent();

    expect(devicesOf($this->tenant)->pluck('token')->all())->toBe(['ExponentPushToken[tablet]']);
});

it('stops pushing to the phone when its token is revoked at sign-out', function (): void {
    $this->actingAsUser($this->customer)->postJson('/api/v1/push-devices', devicePayload())->assertNoContent();

    $this->deleteJson('/api/v1/auth/tokens/current')->assertNoContent();

    expect(devicesOf($this->tenant))->toBeEmpty();
});

it('keeps tenants apart even for the same phone', function (): void {
    $globex = Tenant::factory()->create(['slug' => 'globex']);
    $foreign = $this->userIn($globex, Role::Customer);

    $this->actingAsUser($foreign)->postJson('/api/v1/push-devices', devicePayload())->assertNoContent();
    $this->actingAsUser($this->customer)->postJson('/api/v1/push-devices', devicePayload(['locale' => 'en']))->assertNoContent();
    $this->deleteJson('/api/v1/push-devices/current')->assertNoContent();

    // The other tenant's registration of the same phone is untouched.
    expect(devicesOf($this->tenant))->toBeEmpty();
    $globexDevices = devicesOf($globex);
    expect($globexDevices)->toHaveCount(1)
        ->and($globexDevices[0]->user_id)->toBe($foreign->id)
        ->and($globexDevices[0]->locale)->toBe('pt-BR');
});

it('updates the notification preferences of the current user', function (): void {
    $this->actingAsUser($this->customer)->getJson('/api/v1/auth/me')
        ->assertJsonPath('data.notification_preferences', ['push_replies' => true, 'push_status_changes' => true]);

    $this->putJson('/api/v1/auth/notification-preferences', ['push_replies' => true, 'push_status_changes' => false])
        ->assertOk()
        ->assertJsonPath('data.notification_preferences.push_status_changes', false);

    $this->inTenant($this->tenant, function (): void {
        $fresh = $this->customer->fresh();
        expect($fresh->push_replies)->toBeTrue()->and($fresh->push_status_changes)->toBeFalse();
    });
});

it('validates the notification preferences', function (): void {
    $this->actingAsUser($this->customer)
        ->putJson('/api/v1/auth/notification-preferences', ['push_replies' => 'maybe'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['push_replies', 'push_status_changes']);
});
