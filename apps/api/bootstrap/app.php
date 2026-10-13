<?php

declare(strict_types=1);

use App\Exceptions\ProblemDetailsRenderer;
use App\Http\Middleware\EnsureDocsAreEnabled;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ValidateSignature;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sanctum SPA cookie authentication for first-party frontends.
        $middleware->statefulApi();

        // The tenant must be known before the user is loaded, because
        // Row-Level Security hides every row until it is set.
        $middleware->api(append: [ResolveTenant::class]);
        $middleware->web(append: [ResolveTenant::class]);
        // Priority order: session -> ResolveTenant -> signature -> auth -> active -> route model binding.
        $middleware->prependToPriorityList(before: AuthenticatesRequests::class, prepend: ResolveTenant::class);
        $middleware->prependToPriorityList(before: AuthenticatesRequests::class, prepend: ValidateSignature::class);
        // Deactivated users get 401 before any model lookup, so 404s cannot reveal which ids exist.
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: EnsureUserIsActive::class);

        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'docs' => EnsureDocsAreEnabled::class,
        ]);

        // API clients never get redirected to a login page.
        $middleware->redirectGuestsTo(fn (Request $request): ?string => null);

        // Only the reverse proxy on the private Docker network is trusted.
        $middleware->trustProxies(at: ['10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', '127.0.0.1']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(new ProblemDetailsRenderer);
    })->create();
