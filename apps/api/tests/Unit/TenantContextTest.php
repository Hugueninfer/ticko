<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Connection;

it('only syncs the database setting on Postgres', function (): void {
    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('getDriverName')->andReturn('sqlite');
    $connection->shouldNotReceive('statement');

    $context = new TenantContext($connection);
    $tenant = new Tenant;
    $tenant->id = '01JTESTTENANT0000000000000';

    $context->set($tenant);
    expect($context->check())->toBeTrue()
        ->and($context->id())->toBe('01JTESTTENANT0000000000000')
        ->and($context->tenant())->toBe($tenant);

    $context->clear();
    expect($context->check())->toBeFalse()->and($context->id())->toBeNull();
});

it('restores the previous tenant after run()', function (): void {
    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('getDriverName')->andReturn('sqlite');

    $context = new TenantContext($connection);
    [$a, $b] = [new Tenant, new Tenant];
    $a->id = 'A';
    $b->id = 'B';

    $context->set($a);
    $seen = $context->run($b, fn () => $context->id());

    expect($seen)->toBe('B')->and($context->id())->toBe('A');

    expect(fn () => $context->run($b, fn () => throw new RuntimeException('boom')))->toThrow(RuntimeException::class)
        ->and($context->id())->toBe('A');
});
