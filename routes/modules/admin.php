<?php

use App\Http\Controllers\AdminAppealController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminStatsController;
use App\Http\Middleware\EnforceDomainAccess;
use App\Http\Middleware\RefreshSsoToken;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Module Routes (admin.ternis.link)
|--------------------------------------------------------------------------
*/
Route::middleware(['ensure.domain:admin', 'auth', RefreshSsoToken::class, EnforceDomainAccess::class])
    ->domain((string) config('domains.admin_host', 'admin.ternis.link'))
    ->name('admin.')
    ->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/stats', [AdminStatsController::class, 'index'])->name('stats');
        Route::get('/stats/{section}', [AdminStatsController::class, 'show'])->name('stats.show');
        Route::get('/insights', [AdminController::class, 'insights'])->name('insights');
        Route::get('/threats', [AdminController::class, 'threats'])->name('threats');
        Route::post('/threats/test', [AdminController::class, 'testThreatAi'])->name('threats.test');
        Route::get('/appeals', [AdminAppealController::class, 'index'])->name('appeals');
        Route::post('/appeals/{appeal}/approve', [AdminAppealController::class, 'approve'])->name('appeals.approve');
        Route::post('/appeals/{appeal}/reject', [AdminAppealController::class, 'reject'])->name('appeals.reject');
        Route::get('/links', [AdminController::class, 'links'])->name('links');
        Route::get('/bio', [AdminController::class, 'bio'])->name('bio');
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::get('/domains', [AdminController::class, 'domains'])->name('domains');
        Route::get('/activity', [AdminController::class, 'activity'])->name('activity');
        Route::get('/errors', [AdminController::class, 'errors'])->name('errors');
        Route::get('/allowed-clients', [AdminController::class, 'allowedClients'])->name('allowed-clients');
    });

// Legacy /admin/* on the admin host → root equivalents (permanent).
Route::middleware(['ensure.domain:admin'])
    ->domain((string) config('domains.admin_host', 'admin.ternis.link'))
    ->get('/admin{any?}', function () {
        $suffix = substr(request()->getPathInfo(), strlen('/admin'));
        $query = request()->getQueryString();
        $qs = $query ? '?'.$query : '';

        return redirect('/'.ltrim($suffix, '/').$qs, 301);
    })->where('any', '.*');
