<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Tenant;
use App\Support\Tenancy\MissingTenantException;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Scopes a model to the current tenant. This is the application-level layer;
 * Postgres Row-Level Security enforces the same rule underneath.
 *
 * Without a tenant context the scope matches nothing (fail closed).
 *
 * @mixin Model
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $tenantId = app(TenantContext::class)->id();

            if ($tenantId === null) {
                $builder->whereRaw('1 = 0');

                return;
            }

            $builder->where($builder->qualifyColumn('tenant_id'), $tenantId);
        });

        static::creating(function (Model $model): void {
            $tenantId = app(TenantContext::class)->id();

            if ($tenantId === null) {
                throw MissingTenantException::forModel($model::class);
            }

            // tenant_id is never mass assignable; it always comes from the context.
            $model->setAttribute('tenant_id', $tenantId);
        });
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
