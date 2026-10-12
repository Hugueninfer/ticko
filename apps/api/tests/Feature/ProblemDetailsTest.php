<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Tenant;
use Illuminate\Support\Facades\Route;

it('renders validation errors as RFC 7807 problem details', function (): void {
    $user = $this->userIn(Tenant::factory()->create(), Role::Customer);

    $this->actingAsUser($user)->postJson('/api/v1/tickets', [])
        ->assertUnprocessable()
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJson([
            'type' => 'https://ticko.dev/problems/validation-error',
            'title' => 'The given data was invalid.',
            'status' => 422,
            'instance' => '/api/v1/tickets',
        ])
        ->assertJsonStructure(['detail', 'errors' => ['subject', 'description']]);
});

it('renders unknown routes and methods as problem details', function (): void {
    $this->getJson('/api/v1/nope')->assertNotFound()->assertJsonPath('type', 'https://ticko.dev/problems/not-found');
    $this->putJson('/api/v1/auth/tokens')->assertStatus(405)->assertJsonPath('type', 'https://ticko.dev/problems/http-405');
});

it('hides internal error details unless debugging', function (bool $debug, string $detail): void {
    config(['app.debug' => $debug]);
    Route::get('api/v1/_boom', fn () => throw new RuntimeException('database password is hunter2'));

    $this->getJson('/api/v1/_boom')
        ->assertStatus(500)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('detail', $detail)
        ->assertJsonMissingPath('trace');
})->with([
    'production' => [false, 'An unexpected error occurred.'],
    'debug' => [true, 'database password is hunter2'],
]);

it('reports conflicts and other HTTP errors with their status', function (): void {
    Route::get('api/v1/_conflict', fn () => abort(409, 'Already done.'));

    $this->getJson('/api/v1/_conflict')
        ->assertStatus(409)
        ->assertJson(['type' => 'https://ticko.dev/problems/conflict', 'detail' => 'Already done.']);
});

it('leaves non-API errors to the framework', function (): void {
    $response = $this->get('/definitely-missing')->assertNotFound();

    expect($response->headers->get('Content-Type'))->not->toBe('application/problem+json');
});
