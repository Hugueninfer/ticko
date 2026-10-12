<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\PersonalAccessToken;
use App\Models\Tenant;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    $this->user = $this->userIn($this->tenant, Role::Agent);
});

it('hashes passwords with Argon2id', function (): void {
    expect($this->user->password)->toStartWith('$argon2id$')
        ->and(Hash::info($this->user->password)['algoName'])->toBe('argon2id');
});

it('changes the password and revokes other tokens', function (): void {
    $this->inTenant($this->tenant, fn () => $this->user->createToken('other-device'));

    $this->actingAsUser($this->user)->putJson('/api/v1/auth/password', [
        'current_password' => UserFactory::PASSWORD,
        'password' => 'a-brand-new-long-passphrase',
        'password_confirmation' => 'a-brand-new-long-passphrase',
    ])->assertNoContent();

    expect(PersonalAccessToken::count())->toBe(1);
    $this->inTenant($this->tenant, fn () => expect(Hash::check('a-brand-new-long-passphrase', $this->user->fresh()->password))->toBeTrue());
});

it('rejects weak passwords and a wrong current password', function (array $payload, string $field): void {
    $this->actingAsUser($this->user)
        ->putJson('/api/v1/auth/password', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'too short' => [['current_password' => UserFactory::PASSWORD, 'password' => 'short', 'password_confirmation' => 'short'], 'password'],
    'wrong current' => [['current_password' => 'nope-nope-nope', 'password' => 'a-brand-new-long-passphrase', 'password_confirmation' => 'a-brand-new-long-passphrase'], 'current_password'],
]);

it('rejects passwords that appear in known breaches', function (): void {
    $password = 'a-brand-new-long-passphrase';
    $hash = strtoupper(sha1($password));

    $this->breachedPasswordRange = substr($hash, 5).':42';

    $this->actingAsUser($this->user)->putJson('/api/v1/auth/password', [
        'current_password' => UserFactory::PASSWORD,
        'password' => $password,
        'password_confirmation' => $password,
    ])->assertUnprocessable()->assertJsonValidationErrors('password');
});
