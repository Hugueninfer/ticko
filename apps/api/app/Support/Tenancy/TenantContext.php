<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Models\Tenant;
use Illuminate\Database\Connection;

/**
 * Holds the tenant of the current request (or job) and mirrors it into the
 * Postgres session setting used by the Row-Level Security policies.
 */
final class TenantContext
{
    private ?Tenant $tenant = null;

    public function __construct(private readonly Connection $connection) {}

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
        $this->sync($tenant->id);
    }

    public function clear(): void
    {
        $this->tenant = null;
        $this->sync('');
    }

    public function tenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?string
    {
        return $this->tenant?->id;
    }

    public function check(): bool
    {
        return $this->tenant !== null;
    }

    /**
     * Run a callback inside the given tenant, restoring the previous context afterwards.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public function run(Tenant $tenant, callable $callback): mixed
    {
        $previous = $this->tenant;
        $this->set($tenant);

        try {
            return $callback();
        } finally {
            $previous !== null ? $this->set($previous) : $this->clear();
        }
    }

    private function sync(string $tenantId): void
    {
        if ($this->connection->getDriverName() !== 'pgsql') {
            return;
        }

        $this->connection->statement("select set_config('app.tenant_id', ?, false)", [$tenantId]);
    }
}
