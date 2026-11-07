<?php

use App\Http\Controllers\DocsController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'coming-soon')->name('home');

// Public documentation site. The .md routes come first so they win over docs.show.
Route::get('docs', [DocsController::class, 'index'])->name('docs.index');
Route::get('docs/index.md', [DocsController::class, 'rawIndex'])->name('docs.raw.index');
Route::get('docs/{slug}.md', [DocsController::class, 'raw'])->where('slug', '[A-Za-z0-9][A-Za-z0-9\-/]*')->name('docs.raw');
Route::get('docs/{slug}', [DocsController::class, 'show'])->where('slug', '[A-Za-z0-9][A-Za-z0-9\-/]*')->name('docs.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
