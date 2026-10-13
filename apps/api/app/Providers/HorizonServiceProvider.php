<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Unlike Horizon's default, the dashboard is never open to guests, not
     * even in the local environment.
     */
    protected function authorization(): void
    {
        $this->gate();

        Horizon::auth(fn (Request $request): bool => Gate::check('viewHorizon', [$request->user()]));
    }

    /**
     * Admins only; outside the local environment the admin's e-mail must also
     * be listed in TICKO_HORIZON_ADMINS, since Horizon shows every tenant's jobs.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function (?User $user = null): bool {
            if ($user === null || ! $user->is_active || ! $user->isAdmin()) {
                return false;
            }

            return $this->app->environment('local')
                || in_array($user->email, (array) config('ticko.horizon_admins'), true);
        });
    }
}
