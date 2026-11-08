<?php

use App\Http\Controllers\Api\V1\ClickController;
use App\Http\Controllers\Api\V3\ApiKeyController;
use App\Http\Controllers\Api\V3\DiscoveryController;
use App\Http\Controllers\Api\V3\LinkController;
use App\Http\Middleware\ApiV3Response;
use App\Http\Middleware\AuthenticateApi;
use App\Http\Middleware\LogApiRequest;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Version 3 Routes (links.t-api.de/v3)
|--------------------------------------------------------------------------
| Supports preferred domain auto-resolution (with href.nz fallback),
| structured JSON responses, enhanced metadata, and link management.
*/

Route::middleware(['ensure.domain:api', ApiV3Response::class, 'ensure.api-version:3', LogApiRequest::class])
    ->name('api.v3.')
    ->group(function () {
        Route::get('/', [DiscoveryController::class, 'show'])->middleware('throttle:api');

        Route::middleware([AuthenticateApi::class, 'throttle:api'])->group(function () {
            Route::get('me', [DiscoveryController::class, 'me']);
            Route::get('domains', [DiscoveryController::class, 'domains']);

            Route::apiResource('links', LinkController::class);
            Route::get('links/{link}/clicks', [ClickController::class, 'index']);
            Route::get('links/{link}/clicks/summary', [ClickController::class, 'summary']);

            Route::apiResource('api-keys', ApiKeyController::class)->parameters(['api-keys' => 'apiKey']);
        });

        Route::any('{unmatched}', fn () => abort(404))->where('unmatched', '.*');
    });
