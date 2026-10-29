<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SpamAppealController;
use App\Http\Middleware\EnforceDomainAccess;
use App\Http\Middleware\RefreshSsoToken;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dash Module Routes (dash.ternis.link)
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', function () {
    $host = request()->getHost();
    $type = request()->attributes->get('domain_type');
    $dashHost = (string) config('domains.dashboard_host', 'dash.ternis.link');

    if (in_array($host, ['localhost', '127.0.0.1', '::1', 'testserver'], true)) {
        if (! auth()->check()) {
            return redirect('/login');
        }

        return app(DashboardController::class)->index(request());
    }

    if ($type === 'dashboard') {
        return redirect('/', 301);
    }

    if ($type === 'public-dashboard') {
        return redirect('/', 301);
    }

    if ($host === 'ternis.link' || $type === 'ternis') {
        $target = request()->getScheme().'://'.$dashHost;

        return redirect()->away($target, 302);
    }

    abort(404);
});

Route::get('/dashboard/{any}', function (string $any) {
    $host = request()->getHost();
    $type = request()->attributes->get('domain_type');
    $dashHost = (string) config('domains.dashboard_host', 'dash.ternis.link');
    $query = request()->getQueryString();
    $qs = $query ? '?'.$query : '';

    if ($type === 'dashboard') {
        return redirect('/'.$any.$qs, 301);
    }

    if ($type === 'public-dashboard') {
        return redirect('/'.$any.$qs, 301);
    }

    if ($host === 'ternis.link' || $type === 'ternis') {
        $target = request()->getScheme().'://'.$dashHost.'/'.$any.$qs;

        return redirect()->away($target, 302);
    }

    abort(404);
})->where('any', '.*');

Route::middleware(['ensure.domain:dashboard', 'auth', RefreshSsoToken::class, EnforceDomainAccess::class])->group(function () {

    // /admin on the dashboard host belongs to the admin host.
    Route::domain((string) config('domains.dashboard_host', 'dash.ternis.link'))->get('/admin{any?}', function () {
        $adminHost = (string) config('domains.admin_host', 'admin.ternis.link');
        $suffix = substr(request()->getRequestUri(), strlen('/admin'));

        return redirect()->away(request()->getScheme().'://'.$adminHost.$suffix, 302);
    })->where('any', '.*');

    // Dashboard home. Pinned to the dashboard host.
    Route::domain((string) config('domains.dashboard_host', 'dash.ternis.link'))
        ->get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::domain((string) config('domains.dashboard_host', 'dash.ternis.link'))
        ->get('/new', [DashboardController::class, 'createLink'])->name('dashboard.new');
    Route::domain((string) config('domains.dashboard_host', 'dash.ternis.link'))
        ->get('/links', [DashboardController::class, 'links'])->name('dashboard.links');
    Route::get('/links/create', [DashboardController::class, 'createLink'])->name('dashboard.links.create');
    Route::get('/links/import', [DashboardController::class, 'importLinks'])->name('dashboard.links.import');
    Route::get('/links/export', [DashboardController::class, 'exportLinks'])->name('dashboard.links.export-all');
    Route::get('/links/qr-zip', [DashboardController::class, 'qrZip'])->name('dashboard.links.qr-zip');
    Route::get('/links/{link}', [DashboardController::class, 'showLink'])->name('dashboard.links.show');
    Route::get('/links/{link}/edit', [DashboardController::class, 'editLink'])->name('dashboard.links.edit');
    Route::get('/links/{link}/export', [DashboardController::class, 'exportClicks'])->name('dashboard.links.export');
    Route::get('/links/{link}/qr', [DashboardController::class, 'qrCode'])->name('dashboard.links.qr');
    Route::post('/links/{link}/duplicate', [DashboardController::class, 'duplicate'])->name('dashboard.links.duplicate');
    Route::get('/appeals/create', [SpamAppealController::class, 'create'])->name('dashboard.appeals.create');
    Route::post('/appeals', [SpamAppealController::class, 'store'])->name('dashboard.appeals.store');

    // Link Groups
    Route::get('/groups', [DashboardController::class, 'groups'])->name('dashboard.groups');
    Route::get('/groups/create', [DashboardController::class, 'createGroup'])->name('dashboard.groups.create');
    Route::post('/groups', [DashboardController::class, 'storeGroup'])->name('dashboard.groups.store');
    Route::get('/groups/{group}', [DashboardController::class, 'showGroup'])->name('dashboard.groups.show');
    Route::get('/groups/{group}/edit', [DashboardController::class, 'editGroup'])->name('dashboard.groups.edit');
    Route::put('/groups/{group}', [DashboardController::class, 'updateGroup'])->name('dashboard.groups.update');
    Route::delete('/groups/{group}', [DashboardController::class, 'deleteGroup'])->name('dashboard.groups.delete');
    Route::post('/groups/{group}/links', [DashboardController::class, 'addGroupLink'])->name('dashboard.groups.links.add');
    Route::delete('/groups/{group}/links/{link}', [DashboardController::class, 'removeGroupLink'])->name('dashboard.groups.links.remove');
    Route::post('/groups/{group}/subgroups', [DashboardController::class, 'storeSubgroup'])->name('dashboard.groups.subgroups.store');

    Route::get('/api-keys', [DashboardController::class, 'apiKeys'])->name('dashboard.api-keys');
    Route::get('/api-keys/{key}', [DashboardController::class, 'showApiKey'])->name('dashboard.api-keys.show');
    Route::get('/bio', [DashboardController::class, 'bio'])->name('dashboard.bio');
    Route::get('/bio/{page}', [DashboardController::class, 'showBio'])->name('dashboard.bio.show');
    Route::get('/bio/{page}/build', [DashboardController::class, 'buildBio'])->name('dashboard.bio.build');
    Route::get('/bio/{page}/qr', [DashboardController::class, 'bioQr'])->name('dashboard.bio.qr');
    Route::get('/domains', [DashboardController::class, 'domains'])->name('dashboard.domains');
    Route::get('/notifications', [DashboardController::class, 'notifications'])->name('dashboard.notifications');
    Route::post('/notifications/read', [DashboardController::class, 'markAllNotificationsRead'])->name('dashboard.notifications.read-all');
    Route::post('/notifications/{id}/read', [DashboardController::class, 'markNotificationRead'])->name('dashboard.notifications.read');
    Route::get('/activity', [DashboardController::class, 'activity'])->name('dashboard.activity');
    Route::get('/settings', [DashboardController::class, 'settings'])->name('dashboard.settings');
    Route::get('/settings/export/{export}/download', [DashboardController::class, 'downloadExport'])->name('dashboard.settings.export-download');
});
