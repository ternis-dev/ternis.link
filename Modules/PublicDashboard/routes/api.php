<?php

use Illuminate\Support\Facades\Route;
use Modules\PublicDashboard\Http\Controllers\PublicDashboardController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('publicdashboards', PublicDashboardController::class)->names('publicdashboard');
});
