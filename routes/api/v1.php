<?php

use App\Http\Controllers\Api\V1\ClickController;
use App\Http\Controllers\Api\V1\DomainController;
use App\Http\Controllers\Api\V1\LinkController;
use App\Http\Controllers\Api\V1\PublicLinkController;
use App\Http\Middleware\AuthenticateApi;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes — links.t-api.de/v1/*
|--------------------------------------------------------------------------
*/

// Public: anonymous link creation on public domains (IP-throttled, no auth).
Route::middleware('throttle:10,1')->post('links/public', [PublicLinkController::class, 'store']);

Route::middleware([AuthenticateApi::class, 'throttle:api'])->group(function () {
    // Links CRUD
    Route::apiResource('links', LinkController::class);

    // Custom domains
    Route::get('domains', [DomainController::class, 'index']);
    Route::post('domains', [DomainController::class, 'store']);
    Route::get('domains/{domain}', [DomainController::class, 'show']);
    Route::post('domains/{domain}/verify', [DomainController::class, 'verify']);
    Route::delete('domains/{domain}', [DomainController::class, 'destroy']);

    // Click analytics
    Route::get('links/{link}/clicks', [ClickController::class, 'index']);
    Route::get('links/{link}/clicks/summary', [ClickController::class, 'summary']);
});
