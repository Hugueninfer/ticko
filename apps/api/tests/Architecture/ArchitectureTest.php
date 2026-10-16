<?php

declare(strict_types=1);

use App\Http\Attributes\SelfService;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Knuckles\Scribe\Attributes\Unauthenticated;

arch('strict types everywhere')
    ->expect(['App', 'Database\Factories', 'Tests'])
    ->toUseStrictTypes();

arch('no debugging or unsafe functions')->preset()->php();
arch('no security-sensitive functions')->preset()->security()->ignoring('App\Services\ImageSanitizer');

arch('controllers are thin')
    ->expect('App\Http\Controllers')
    ->toExtend('App\Http\Controllers\Controller')
    ->ignoring('App\Http\Controllers\Controller')
    ->not->toUse(['Illuminate\Support\Facades\DB', 'Illuminate\Database\Eloquent\Builder']);

arch('API controllers are final')->expect('App\Http\Controllers\Api')->toBeFinal();

arch('form requests')
    ->expect('App\Http\Requests')
    ->toExtend('Illuminate\Foundation\Http\FormRequest')
    ->toHaveSuffix('Request');

arch('resources')
    ->expect('App\Http\Resources')
    ->toExtend('Illuminate\Http\Resources\Json\JsonResource')
    ->toHaveSuffix('Resource');

arch('policies')->expect('App\Policies')->toBeFinal()->toHaveSuffix('Policy');

// The AI never answers a customer by itself: nothing on the AI side can post
// a message; only ApproveReplySuggestion (an agent's decision) does.
arch('the AI cannot send messages')
    ->expect(['App\Services\Ai', 'App\Services\Knowledge', 'App\Services\Suggestions', 'App\Jobs\GenerateReplySuggestion', 'App\Jobs\IndexKnowledgeArticle'])
    ->not->toUse(['App\Actions\Tickets\PostTicketMessage', 'App\Actions\Suggestions\ApproveReplySuggestion']);

arch('AI providers are reached only through their interfaces')
    ->expect('App\Services\Ai\Providers')
    ->toOnlyBeUsedIn(['App\Services\Ai', 'App\Providers\AiServiceProvider']);

arch('actions do not depend on HTTP')
    ->expect('App\Actions')
    ->toBeReadonly()
    ->not->toUse(['Illuminate\Http\Request', 'Illuminate\Foundation\Http\FormRequest']);

it('declares explicit $fillable on every model and no $guarded escape hatch', function (): void {
    foreach (glob(app_path('Models/*.php')) as $file) {
        $class = 'App\\Models\\'.basename($file, '.php');
        $model = new $class;
        assert($model instanceof Model);

        expect($model->getFillable())->not->toBeEmpty("{$class} must declare \$fillable")
            ->and($model->getFillable())->not->toContain('tenant_id', 'id')
            ->and($model->getGuarded())->toBe(['*'], "{$class} must not change \$guarded");
    }
});

it('scopes every model with a tenant_id column to the tenant', function (): void {
    $scoped = ['User', 'Category', 'Ticket', 'TicketMessage', 'Attachment', 'PushDevice', 'KbArticle', 'KbChunk', 'AiSuggestion', 'TicketClassification', 'ClassificationCorrection', 'AuditEvent'];

    foreach ($scoped as $name) {
        expect(class_uses_recursive('App\\Models\\'.$name))->toHaveKey(BelongsToTenant::class);
    }
});

it('authorizes every API controller action', function (): void {
    // Broadcasting auth (api/broadcasting/auth) is the framework's controller;
    // its authorization lives in routes/channels.php (see BroadcastingTest).
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_starts_with($route->uri(), 'api/v1/'));

    expect($routes)->not->toBeEmpty();

    foreach ($routes as $route) {
        [$class, $method] = [...explode('@', $route->getActionName()), '__invoke'];
        $reflection = new ReflectionMethod($class, $method);
        $middleware = $route->gatherMiddleware();
        $label = "{$class}::{$method} ({$route->uri()})";

        if ($reflection->getAttributes(Unauthenticated::class) !== []) {
            // Public endpoints must be either rate limited or protected by a signature.
            expect(collect($middleware)->contains(fn (string $m): bool => str_starts_with($m, 'throttle:') || $m === 'signed'))
                ->toBeTrue("{$label} is public but neither throttled nor signed");

            continue;
        }

        expect(in_array('auth:sanctum', $middleware, true))->toBeTrue("{$label} must require authentication");

        if ($reflection->getAttributes(SelfService::class) !== []) {
            continue;
        }

        $source = implode('', array_slice(
            file((string) $reflection->getFileName()),
            $reflection->getStartLine() - 1,
            $reflection->getEndLine() - $reflection->getStartLine() + 1,
        ));

        expect($source)->toMatch('/Gate::authorize\(/', "{$label} never calls Gate::authorize()");
    }
});
