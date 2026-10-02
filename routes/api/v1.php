<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\ActivityController;
use App\Http\Controllers\Api\V1\ApiKeyController;
use App\Http\Controllers\Api\V1\BioController;
use App\Http\Controllers\Api\V1\BulkOperationController;
use App\Http\Controllers\Api\V1\ClickController;
use App\Http\Controllers\Api\V1\DomainController;
use App\Http\Controllers\Api\V1\LinkController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PublicLinkController;
use App\Http\Controllers\Api\V1\PublicQrCodeController;
use App\Http\Controllers\Api\V1\SettingsController;
use App\Http\Controllers\Api\V1\VersionController;
use App\Http\Middleware\AuthenticateApi;
use App\Http\Middleware\LogApiRequest;
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
Route::middleware(['ensure.domain:api', 'ensure.api-version:1', LogApiRequest::class, 'throttle:api'])->get('/', [VersionController::class, 'show']);

// Public: anonymous link creation (IP-throttled, no auth).
Route::middleware(['ensure.domain:api,public', 'ensure.api-version:1', LogApiRequest::class, 'throttle:10,1'])->post('links/public', [PublicLinkController::class, 'store']);
Route::middleware(['ensure.domain:api,public', 'ensure.api-version:1', LogApiRequest::class, 'throttle:10,1'])->get('qr', PublicQrCodeController::class);

Route::middleware(['ensure.domain:api', 'ensure.api-version:1', LogApiRequest::class, AuthenticateApi::class, 'throttle:api'])->group(function () {
    // Links CRUD
    Route::get('links/{link}/qr', [LinkController::class, 'qr']);
    Route::apiResource('links', LinkController::class);

    // Custom domains
    Route::get('domains', [DomainController::class, 'index']);
    Route::post('domains', [DomainController::class, 'store']);
    Route::get('domains/{domain}', [DomainController::class, 'show']);
    Route::post('domains/{domain}/verify', [DomainController::class, 'verify']);
    Route::delete('domains/{domain}', [DomainController::class, 'destroy']);

    // API keys (raw token returned once on create, never stored)
    Route::get('api-keys', [ApiKeyController::class, 'index']);
    Route::post('api-keys', [ApiKeyController::class, 'store']);
    Route::get('api-keys/{apiKey}', [ApiKeyController::class, 'show']);
    Route::patch('api-keys/{apiKey}', [ApiKeyController::class, 'update']);
    Route::delete('api-keys/{apiKey}', [ApiKeyController::class, 'destroy']);

    // Notifications inbox (database notifications, newest first)
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/read', [NotificationController::class, 'markAllRead']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'markRead']);

    // Personal activity history
    Route::get('activity', [ActivityController::class, 'index']);

    // Theme/layout + email notification preferences
    Route::get('settings', [SettingsController::class, 'show']);
    Route::patch('settings', [SettingsController::class, 'update']);

    // Click analytics
    Route::get('links/{link}/clicks', [ClickController::class, 'index']);
    Route::get('links/{link}/clicks/summary', [ClickController::class, 'summary']);

    // Privacy self-service
    Route::get('account/data-summary', [AccountController::class, 'dataSummary']);
    Route::post('account/export', [AccountController::class, 'export']);
    Route::get('account/exports', [AccountController::class, 'exports']);
    Route::get('account/exports/{export}/download', [AccountController::class, 'download']);
    Route::post('account/deletion', [AccountController::class, 'scheduleDeletion']);
    Route::delete('account/deletion', [AccountController::class, 'cancelDeletion']);

    // Bulk operations (import + mutate, async with status polling)
    Route::post('links/import', [BulkOperationController::class, 'import']);
    Route::post('links/bulk', [BulkOperationController::class, 'bulk']);
    Route::get('bulk-operations', [BulkOperationController::class, 'index']);
    Route::get('bulk-operations/{operation}', [BulkOperationController::class, 'show']);

    // Link-in-bio pages (custom domains only, v1)
    Route::get('bio-pages', [BioController::class, 'index']);
    Route::post('bio-pages', [BioController::class, 'store']);
    Route::post('bio-pages/from-template', [BioController::class, 'fromTemplate']);
    Route::get('bio-pages/{page}', [BioController::class, 'show']);
    Route::put('bio-pages/{page}', [BioController::class, 'update']);
    Route::delete('bio-pages/{page}', [BioController::class, 'destroy']);
    Route::post('bio-pages/{page}/duplicate', [BioController::class, 'duplicate']);
    Route::put('bio-pages/{page}/buttons', [BioController::class, 'syncButtons']);
    Route::get('bio-pages/{page}/stats', [BioController::class, 'stats']);
    Route::get('bio-pages/{page}/events/export', [BioController::class, 'exportEvents']);
    Route::get('bio-pages/{page}/export', [BioController::class, 'exportDefinition']);
    Route::get('bio-pages/{page}/qr', [BioController::class, 'qr']);
});
