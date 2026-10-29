<?php

use App\Http\Controllers\Api\V1\ClickController;
use App\Http\Controllers\Api\V2\DiscoveryController;
use App\Http\Controllers\Api\V2\LinkController;
use App\Http\Controllers\Api\V2\SecurityScanController;
use App\Http\Controllers\Api\V2\StatsController;
use App\Http\Middleware\ApiV2Response;
use App\Http\Middleware\AuthenticateApi;
use App\Http\Middleware\LogApiRequest;
use Illuminate\Support\Facades\Route;

// v2 intentionally exposes a small, authenticated link-management surface.
// Existing clients and the broader dashboard API remain supported on v1.
Route::middleware(['ensure.domain:api', ApiV2Response::class, 'ensure.api-version:2', LogApiRequest::class])->name('api.v2.')->group(function () {
    Route::get('/', [DiscoveryController::class, 'show'])->middleware('throttle:api');

    // Public link security & threat inspection (TypeSafe AI evaluation).
    Route::match(['get', 'post'], 'scan', [SecurityScanController::class, 'scan'])->middleware('throttle:api');

    // Public aggregate network stats (no personal data).
    Route::middleware('throttle:api')->prefix('stats')->group(function () {
        Route::get('/', [StatsController::class, 'index']);
        Route::get('heatmaps', [StatsController::class, 'heatmaps']);
        Route::get('domains', [StatsController::class, 'domains']);
        Route::get('creations', [StatsController::class, 'creations']);
        Route::get('clicks', [StatsController::class, 'clicks']);
        Route::get('qr', [StatsController::class, 'qr']);
    });

    Route::middleware([AuthenticateApi::class, 'throttle:api'])->group(function () {
        Route::get('me', [DiscoveryController::class, 'me']);
        Route::get('domains', [DiscoveryController::class, 'domains']);
        Route::apiResource('links', LinkController::class);
        Route::get('links/{link}/clicks', [ClickController::class, 'index']);
        Route::get('links/{link}/clicks/summary', [ClickController::class, 'summary']);
    });

    Route::any('{unmatched}', fn () => abort(404))->where('unmatched', '.*');
});
