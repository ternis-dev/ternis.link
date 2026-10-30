<?php

use App\Http\Controllers\StatsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Stats Module Routes (pages/stats)
|--------------------------------------------------------------------------
*/
Route::middleware(['ensure.domain:ternis'])->prefix('pages/stats')->name('pages.stats.')->group(function () {
    Route::get('/', [StatsController::class, 'index'])->name('index');
    Route::get('/activity', [StatsController::class, 'activity'])->name('activity');
    Route::get('/security', [StatsController::class, 'security'])->name('security');
    Route::get('/domains', [StatsController::class, 'domains'])->name('domains');
});

// Markdown twins of stats pages
Route::middleware(['ensure.domain:ternis'])->group(function () {
    Route::get('/pages/stats/activity.md', [StatsController::class, 'activityMd'])->name('pages.stats.activity-md');
    Route::get('/pages/stats/security.md', [StatsController::class, 'securityMd'])->name('pages.stats.security-md');
    Route::get('/pages/stats.md', [StatsController::class, 'indexMd'])->name('pages.stats.index-md');
    Route::get('/pages/stats/domains.md', [StatsController::class, 'domainsMd'])->name('pages.stats.domains-md');
});

// Legacy /stats redirect
Route::middleware(['ensure.domain:ternis'])->get('/stats{any?}', function () {
    $suffix = substr(request()->getPathInfo(), strlen('/stats'));
    $query = request()->getQueryString();
    $qs = $query ? '?'.$query : '';

    if (rtrim($suffix, '/') === '/links') {
        return redirect('/pages/stats'.$qs, 301);
    }

    return redirect('/pages/stats'.rtrim($suffix, '/').$qs, 301);
})->where('any', '.*');
