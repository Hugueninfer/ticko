<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(TenantContext::class, fn (Application $app): TenantContext => new TenantContext(
            $app->make(DatabaseManager::class)->connection(),
        ));
        $this->app->singleton(ImageManager::class, fn () => new ImageManager(new GdDriver));
    }

    public function boot(): void
    {
        // Fail loudly on lazy loading, silently discarded attributes and missing attributes.
        Model::shouldBeStrict(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes();

        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // Rejects passwords found in public breaches (k-anonymity HIBP range API).
        Password::defaults(fn (): Password => Password::min(12)->max(255)->uncompromised());

        Route::model('member', User::class);

        // Dashboard figures cover the whole tenant, so only admins see them.
        Gate::define('viewMetrics', fn (User $user): bool => $user->isAdmin());

        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        // Keyed by tenant + e-mail and by IP, so neither one account nor one
        // client can brute-force credentials; the response does not reveal which.
        RateLimiter::for('auth', function (Request $request): array {
            $limit = $this->limit('auth');
            $email = mb_strtolower($request->string('email')->toString());
            $tenant = (string) $request->header('X-Tenant');

            return [
                Limit::perMinute($limit)->by('auth:account:'.hash('sha256', $tenant.'|'.$email)),
                Limit::perMinute($limit * 4)->by('auth:ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute($this->limit('api'))
            ->by($request->user()?->getAuthIdentifier() ?? $request->ip()));

        RateLimiter::for('uploads', fn (Request $request): Limit => Limit::perMinute($this->limit('uploads'))
            ->by($request->user()?->getAuthIdentifier() ?? $request->ip()));
    }

    private function limit(string $name): int
    {
        return config()->integer("ticko.rate_limits.{$name}");
    }
}
