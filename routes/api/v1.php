<?php

use App\Http\Controllers\Api\V1\ClickController;
use App\Http\Controllers\Api\V1\DomainController;
use App\Http\Controllers\Api\V1\LinkController;
use App\Http\Controllers\Api\V1\PublicLinkController;
use App\Http\Controllers\Api\V1\VersionController;
use App\Http\Middleware\AuthenticateApi;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes — links.t-api.de/v1/*
|--------------------------------------------------------------------------
| Host-pinned via ensure.domain (ResolveDomain runs globally first).
| Version lifecycle via ensure.api-version (410 when retired, Deprecation
| + Sunset headers when deprecated, API-Version headers always).
| Authenticated CRUD stays API-only; the anonymous endpoint also
| accepts public short-link hosts (href.nz landing + API share the
| same quota/throttle), but 404s on dashboard/admin/redirect hosts.
*/

// Version metadata (public, no auth) — also the landing target of
// links.t-api.de/ (/v{latest}/), so it must never 404 while v1 exists.
Route::middleware(['ensure.domain:api', 'ensure.api-version:1', 'throttle:api'])->get('/', [VersionController::class, 'show']);

// Public: anonymous link creation (IP-throttled, no auth).
Route::middleware(['ensure.domain:api,public', 'ensure.api-version:1', 'throttle:10,1'])->post('links/public', [PublicLinkController::class, 'store']);

Route::middleware(['ensure.domain:api', 'ensure.api-version:1', AuthenticateApi::class, 'throttle:api'])->group(function () {
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
