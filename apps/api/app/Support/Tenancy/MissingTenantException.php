<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use LogicException;

final class MissingTenantException extends LogicException
{
    public static function forModel(string $model): self
    {
        return new self("Cannot create [{$model}] without an active tenant context.");
    }
}
