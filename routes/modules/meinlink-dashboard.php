<?php

use App\Http\Controllers\MeinlinkDashboardController;
use Illuminate\Support\Facades\Route;

Route::domain(config('domains.meinlink_dashboard_host'))->name('meinlink-dashboard.')->middleware(['ensure.domain:public-dashboard', 'auth', 'refresh.sso', 'enforce.domain'])->group(function () {
    Route::get('/', [MeinlinkDashboardController::class, 'index'])->name('index');
    Route::get('/links', [MeinlinkDashboardController::class, 'index'])->name('links');
    Route::get('/new', [MeinlinkDashboardController::class, 'create'])->name('create');
    Route::post('/links', [MeinlinkDashboardController::class, 'store'])->middleware('throttle:20,1')->name('store');
    Route::get('/links/{link}', [MeinlinkDashboardController::class, 'show'])->name('show');
    Route::get('/links/{link}/edit', [MeinlinkDashboardController::class, 'edit'])->name('edit');
    Route::put('/links/{link}', [MeinlinkDashboardController::class, 'update'])->middleware('throttle:30,1')->name('update');
    Route::post('/links/{link}/toggle', [MeinlinkDashboardController::class, 'toggle'])->name('toggle');
    Route::get('/links/{link}/qr', [MeinlinkDashboardController::class, 'qr'])->middleware('throttle:30,1')->name('qr');
    Route::view('/help', 'meinlink-dashboard.help')->name('help');
});
