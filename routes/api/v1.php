<?php

use App\Http\Controllers\Api\V1\ClickController;
use App\Http\Controllers\Api\V1\LinkController;
use App\Http\Middleware\AuthenticateApi;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes — links.t-api.de/v1/*
|--------------------------------------------------------------------------
| All routes require API key or SSO token authentication.
*/

Route::middleware(AuthenticateApi::class)->group(function () {
    // Links CRUD
    Route::apiResource('links', LinkController::class);

    // Click analytics
    Route::get('links/{link}/clicks', [ClickController::class, 'index']);
    Route::get('links/{link}/clicks/summary', [ClickController::class, 'summary']);
});
