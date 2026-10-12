<?php

declare(strict_types=1);

it('serves Swagger UI with a strict content security policy', function (): void {
    $response = $this->get('/docs')->assertOk()->assertSee('swagger-ui', false);

    expect($response->headers->get('Content-Security-Policy'))
        ->toContain("default-src 'none'")
        ->toContain("frame-ancestors 'none'");
    expect($response->getContent())->toContain('integrity="sha384-');
});

it('serves the versioned OpenAPI 3 document', function (): void {
    $this->getJson('/docs/openapi.json')
        ->assertOk()
        ->assertJsonPath('info.title', 'Ticko API')
        ->assertJsonStructure(['openapi', 'paths' => ['/api/v1/tickets']]);

    expect($this->getJson('/docs/openapi.json')->json('openapi'))->toStartWith('3.');
});

it('can be disabled', function (): void {
    config(['ticko.docs_enabled' => false]);

    $this->get('/docs')->assertNotFound();
    $this->get('/docs/openapi.json')->assertNotFound();
});

it('answers 404 when the document has not been generated', function (): void {
    config(['ticko.openapi_path' => '/nonexistent/openapi.json']);

    $this->get('/docs/openapi.json')->assertNotFound();
});

it('exposes the root and health endpoints', function (): void {
    $this->getJson('/')->assertOk()->assertJsonPath('name', config('app.name'));
    $this->get('/up')->assertOk();
});
