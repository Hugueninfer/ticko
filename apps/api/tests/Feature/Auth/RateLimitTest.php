<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Tenant;
use Illuminate\Http\UploadedFile;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create(['slug' => 'acme']);
});

it('throttles repeated login attempts for an account', function (): void {
    $attempt = fn () => $this->postJson('/api/v1/auth/tokens', [
        'email' => 'victim@acme.test', 'password' => 'guess-'.random_int(0, 9999), 'device_name' => 'x',
    ], ['X-Tenant' => 'acme']);

    foreach (range(1, config('ticko.rate_limits.auth')) as $_) {
        $attempt()->assertUnprocessable();
    }

    $attempt()
        ->assertTooManyRequests()
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertHeader('Retry-After')
        ->assertJsonPath('status', 429);
});

it('throttles authenticated API usage per user', function (): void {
    config(['ticko.rate_limits.api' => 3]);
    $user = $this->userIn($this->tenant, Role::Agent);
    $this->actingAsUser($user);

    foreach (range(1, 3) as $_) {
        $this->getJson('/api/v1/auth/me')->assertOk();
    }

    $this->getJson('/api/v1/auth/me')->assertTooManyRequests();
});

it('throttles uploads per user', function (): void {
    config(['ticko.rate_limits.uploads' => 1]);
    $user = $this->userIn($this->tenant, Role::Customer);
    $ticket = $this->inTenant($this->tenant, fn () => App\Models\Ticket::factory()->create(['requester_id' => $user->id]));
    $this->actingAsUser($user);

    $this->post("/api/v1/tickets/{$ticket->id}/attachments", ['file' => UploadedFile::fake()->create('a.txt', 1, 'text/plain')])
        ->assertUnprocessable();

    $this->post("/api/v1/tickets/{$ticket->id}/attachments", ['file' => UploadedFile::fake()->create('a.txt', 1, 'text/plain')])
        ->assertTooManyRequests();
});
