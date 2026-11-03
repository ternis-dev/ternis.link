<?php

use Illuminate\Support\Facades\Route;
use Modules\PublicDashboard\Http\Controllers\PublicDashboardController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('publicdashboards', PublicDashboardController::class)->names('publicdashboard');
});
