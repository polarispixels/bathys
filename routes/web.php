<?php

use App\Http\Controllers\DocsController;
use App\Http\Controllers\Web\ShelfController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::get('/', ShelfController::class)->middleware('throttle:60,1')->name('home');

// Public documentation site. Stateless (no session, CSRF, or cookies), so the
// responses are cacheable and a crawler cannot create session rows.
// The .md routes come first so they win over docs.show.
Route::withoutMiddleware([
    StartSession::class,
    ShareErrorsFromSession::class,
    PreventRequestForgery::class,
    ValidateCsrfToken::class,
    AddQueuedCookiesToResponse::class,
])->middleware('throttle:60,1')->group(function () {
    Route::get('docs', [DocsController::class, 'index'])->name('docs.index');
    Route::get('docs/index.md', [DocsController::class, 'rawIndex'])->name('docs.raw.index');
    Route::get('docs/{slug}.md', [DocsController::class, 'raw'])->where('slug', '[A-Za-z0-9][A-Za-z0-9\-/]*')->name('docs.raw');
    Route::get('docs/{slug}', [DocsController::class, 'show'])->where('slug', '[A-Za-z0-9][A-Za-z0-9\-/]*')->name('docs.show');
    Route::get('llms.txt', [DocsController::class, 'llms'])->name('docs.llms');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
