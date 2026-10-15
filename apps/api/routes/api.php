<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AttachmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DemoLoginController;
use App\Http\Controllers\Api\V1\MetricsController;
use App\Http\Controllers\Api\V1\PushDeviceController;
use App\Http\Controllers\Api\V1\RealtimeController;
use App\Http\Controllers\Api\V1\TeamController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\TicketMessageController;
use Illuminate\Support\Facades\Route;

/*
| Every route below runs after ResolveTenant (see bootstrap/app.php), so
| route model binding and queries are already scoped to the request tenant.
*/

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::middleware('throttle:auth')->group(function (): void {
        Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');
        Route::post('auth/tokens', [AuthController::class, 'issueToken'])->name('auth.tokens.store');
    });

    // Demo sign-in has no credentials to brute-force; it is limited per client only.
    Route::middleware('throttle:demo')->group(function (): void {
        Route::get('auth/demo', [DemoLoginController::class, 'show'])->name('auth.demo.show');
        Route::post('auth/demo', [DemoLoginController::class, 'store'])->name('auth.demo.store');
    });

    Route::get('attachments/{attachment}/content', [AttachmentController::class, 'content'])
        ->middleware(['signed', 'throttle:api'])
        ->name('attachments.content');

    Route::middleware(['auth:sanctum', 'active', 'throttle:api'])->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::delete('auth/tokens/current', [AuthController::class, 'revokeToken'])->name('auth.tokens.destroy');
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::put('auth/password', [AuthController::class, 'updatePassword'])->name('auth.password');
        Route::put('auth/notification-preferences', [AuthController::class, 'updateNotificationPreferences'])->name('auth.notifications');

        Route::post('push-devices', [PushDeviceController::class, 'store'])->name('push-devices.store');
        Route::delete('push-devices/current', [PushDeviceController::class, 'destroy'])->name('push-devices.destroy');

        Route::get('realtime', [RealtimeController::class, 'show'])->name('realtime.show');

        Route::get('tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::post('tickets', [TicketController::class, 'store'])->name('tickets.store');
        Route::get('tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
        Route::patch('tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.status');
        Route::patch('tickets/{ticket}/assignee', [TicketController::class, 'assign'])->name('tickets.assign');

        Route::get('tickets/{ticket}/messages', [TicketMessageController::class, 'index'])->name('tickets.messages.index');
        Route::post('tickets/{ticket}/messages', [TicketMessageController::class, 'store'])->name('tickets.messages.store');

        Route::get('tickets/{ticket}/attachments', [AttachmentController::class, 'index'])->name('tickets.attachments.index');
        Route::post('tickets/{ticket}/attachments', [AttachmentController::class, 'store'])
            ->middleware('throttle:uploads')
            ->name('tickets.attachments.store');
        Route::get('attachments/{attachment}', [AttachmentController::class, 'show'])->name('attachments.show');

        Route::apiResource('categories', CategoryController::class);

        Route::get('metrics', [MetricsController::class, 'show'])->name('metrics.show');

        Route::get('team', [TeamController::class, 'index'])->name('team.index');
        Route::post('team', [TeamController::class, 'store'])->name('team.store');
        Route::get('team/{member}', [TeamController::class, 'show'])->name('team.show');
        Route::patch('team/{member}', [TeamController::class, 'update'])->name('team.update');
    });
});
