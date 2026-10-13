<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Tenant;
use App\Support\OpenApi\RequiredResponseFields;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\ServiceProvider;
use Knuckles\Scribe\Scribe;
use Symfony\Component\Yaml\Yaml;

/**
 * Development-only hooks for `composer docs` (Scribe is a dev dependency).
 *
 * Example responses are built from factories inside a rolled-back
 * transaction; they need a tenant context because RLS applies to them too.
 * The generated OpenAPI document is exported as JSON to a versioned file,
 * with every response field required unless a resource marks it optional.
 */
final class ApiDocsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! $this->app->runningInConsole() || ! class_exists(Scribe::class)) {
            return;
        }

        Scribe::bootstrap(function (): void {
            $tenant = Tenant::query()->firstOrCreate(['slug' => 'docs-example'], ['name' => 'Example Inc.']);
            $this->app->make(TenantContext::class)->set($tenant);
        });

        Scribe::afterGenerating(function (array $paths): void {
            if (! is_string($paths['openapi'] ?? null)) {
                return;
            }

            /** @var array<string, mixed> $spec */
            $spec = Yaml::parseFile($paths['openapi']);
            $spec = RequiredResponseFields::fromResources()->apply($spec);
            $json = json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

            file_put_contents(config()->string('ticko.openapi_path'), $json.PHP_EOL);
        });
    }
}
