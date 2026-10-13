<?php

declare(strict_types=1);

use App\Http\Controllers\DocsController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json(['name' => config('app.name'), 'docs' => url('/docs')]));

Route::middleware('docs')->group(function (): void {
    Route::get('/docs', [DocsController::class, 'ui'])->name('docs.ui');
    Route::get('/docs/openapi.json', [DocsController::class, 'spec'])->name('docs.spec');
});
