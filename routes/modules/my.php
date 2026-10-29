<?php

use App\Http\Controllers\PublicDashboardController;
use App\Http\Controllers\PublicGroupController;
use App\Http\Controllers\SpamAppealController;
use App\Http\Middleware\EnforceDomainAccess;
use App\Http\Middleware\RefreshSsoToken;
use App\Support\PublicDashboard;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| My / Public Dashboard Module (my.href.nz)
|--------------------------------------------------------------------------
*/

$publicDashboardRoutes = function (string $prefix): void {
    Route::get('/', [PublicDashboardController::class, 'index'])->name(rtrim($prefix, '.'));
    Route::get('/new', [PublicDashboardController::class, 'createLink'])->name($prefix.'new');
    Route::get('/links', [PublicDashboardController::class, 'links'])->name($prefix.'links');
    Route::get('/links/create', [PublicDashboardController::class, 'createLink'])->name($prefix.'links.create');
    Route::get('/links/import', [PublicDashboardController::class, 'importLinks'])->name($prefix.'links.import');
    Route::get('/links/export', [PublicDashboardController::class, 'exportLinks'])->name($prefix.'links.export-all');
    Route::get('/links/qr-zip', [PublicDashboardController::class, 'qrZip'])->name($prefix.'links.qr-zip');
    Route::get('/links/{link}', [PublicDashboardController::class, 'showLink'])->name($prefix.'links.show');
    Route::get('/links/{link}/edit', [PublicDashboardController::class, 'editLink'])->name($prefix.'links.edit');
    Route::get('/links/{link}/export', [PublicDashboardController::class, 'exportClicks'])->name($prefix.'links.export');
    Route::get('/links/{link}/qr', [PublicDashboardController::class, 'qrCode'])->name($prefix.'links.qr');
    Route::post('/links/{link}/duplicate', [PublicDashboardController::class, 'duplicate'])->name($prefix.'links.duplicate');
    Route::get('/appeals/create', [SpamAppealController::class, 'create'])->name($prefix.'appeals.create');
    Route::post('/appeals', [SpamAppealController::class, 'store'])->name($prefix.'appeals.store');

    // Account sections — also reachable from my.ternis.link.
    Route::get('/api-keys', [PublicDashboardController::class, 'apiKeys'])->name($prefix.'api-keys');
    Route::get('/api-keys/{key}', [PublicDashboardController::class, 'showApiKey'])->name($prefix.'api-keys.show');
    Route::get('/domains', [PublicDashboardController::class, 'domains'])->name($prefix.'domains');
    Route::get('/settings', [PublicDashboardController::class, 'settings'])->name($prefix.'settings');
    Route::get('/notifications', [PublicDashboardController::class, 'notifications'])->name($prefix.'notifications');
    Route::post('/notifications/read', [PublicDashboardController::class, 'markAllNotificationsRead'])->name($prefix.'notifications.read-all');
    Route::post('/notifications/{id}/read', [PublicDashboardController::class, 'markNotificationRead'])->name($prefix.'notifications.read');

    // Link groups — the public dashboard groups its own shortener links
    Route::get('/insights', [PublicDashboardController::class, 'insights'])->name($prefix.'insights');
    Route::get('/groups', [PublicGroupController::class, 'groups'])->name($prefix.'groups');
    Route::get('/groups/create', [PublicGroupController::class, 'createGroup'])->name($prefix.'groups.create');
    Route::post('/groups', [PublicGroupController::class, 'storeGroup'])->name($prefix.'groups.store');
    Route::get('/groups/{group}', [PublicGroupController::class, 'showGroup'])->name($prefix.'groups.show');
    Route::get('/groups/{group}/edit', [PublicGroupController::class, 'editGroup'])->name($prefix.'groups.edit');
    Route::put('/groups/{group}', [PublicGroupController::class, 'updateGroup'])->name($prefix.'groups.update');
    Route::delete('/groups/{group}', [PublicGroupController::class, 'deleteGroup'])->name($prefix.'groups.delete');
    Route::post('/groups/{group}/links', [PublicGroupController::class, 'addGroupLink'])->name($prefix.'groups.links.add');
    Route::delete('/groups/{group}/links/{link}', [PublicGroupController::class, 'removeGroupLink'])->name($prefix.'groups.links.remove');
    Route::post('/groups/{group}/subgroups', [PublicGroupController::class, 'storeSubgroup'])->name($prefix.'groups.subgroups.store');
    Route::get('/settings/export/{export}/download', [PublicDashboardController::class, 'downloadExport'])->name($prefix.'settings.export-download');
};

Route::domain(PublicDashboard::canonicalHost())
    ->middleware(['ensure.domain:public-dashboard', 'auth', RefreshSsoToken::class, EnforceDomainAccess::class])
    ->group(fn () => $publicDashboardRoutes('public-dashboard.'));
