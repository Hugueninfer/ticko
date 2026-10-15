<?php

declare(strict_types=1);

use App\Support\OpenApi\RequiredResponseFields;

function responseSpec(array $data): array
{
    return ['paths' => ['/api/x' => ['get' => ['responses' => [
        '200' => ['content' => ['application/json' => ['schema' => [
            'type' => 'object',
            'properties' => ['data' => $data, 'meta' => ['type' => 'object', 'properties' => ['total' => ['type' => 'integer']]]],
        ]]]],
        '404' => ['content' => ['application/json' => ['schema' => ['type' => 'object', 'properties' => ['title' => ['type' => 'string']]]]]],
    ]]]]];
}

it('marks every response property required except the declared optional ones', function (): void {
    $spec = responseSpec(['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
        'id' => ['type' => 'string'],
        'count' => ['type' => 'integer'],
        'owner' => ['type' => 'object', 'required' => ['id'], 'properties' => ['id' => ['type' => 'string'], 'email' => ['type' => 'string']]],
    ]]]);

    $result = (new RequiredResponseFields(['count', 'owner.email']))->apply($spec);
    $schema = $result['paths']['/api/x']['get']['responses']['200']['content']['application/json']['schema'];
    $item = $schema['properties']['data']['items'];

    expect($schema['required'])->toBe(['data', 'meta'])
        ->and($schema['properties']['meta']['required'])->toBe(['total'])
        ->and($item['required'])->toBe(['id', 'owner'])
        ->and($item['properties']['owner']['required'])->toBe(['id'])
        // Error responses are left alone.
        ->and($result['paths']['/api/x']['get']['responses']['404']['content']['application/json']['schema'])->not->toHaveKey('required');
});

it('reads the optional fields from the API resources', function (): void {
    $spec = responseSpec(['type' => 'object', 'properties' => [
        'id' => ['type' => 'string'],
        'tenant' => ['type' => 'object', 'properties' => ['id' => ['type' => 'string']]],
        'open_tickets_count' => ['type' => 'integer'],
    ]]);

    $data = RequiredResponseFields::fromResources()->apply($spec)['paths']['/api/x']['get']['responses']['200']['content']['application/json']['schema']['properties']['data'];

    expect($data['required'])->toBe(['id'])
        ->and($data['properties']['tenant']['required'])->toBe(['id']);
});

it('ignores documents without paths', function (): void {
    expect((new RequiredResponseFields([]))->apply(['openapi' => '3.0.3']))->toBe(['openapi' => '3.0.3']);
});
