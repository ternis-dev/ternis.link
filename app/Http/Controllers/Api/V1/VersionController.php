<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiVersion;
use Illuminate\Http\JsonResponse;

class VersionController extends Controller
{
    /**
     * GET /v1/ — version metadata (public, no auth).
     *
     * This is also the landing target of links.t-api.de/ (/v{latest}/),
     * so it must never 404 while the version exists.
     */
    public function show(): JsonResponse
    {
        $version = 1;
        $record = ApiVersion::where('version', $version)->first();

        return response()->json([
            'version' => $version,
            'status' => $record?->status->value ?? 'active',
            'latest_version' => ApiVersion::latestVersion(),
            'deprecated_at' => $record?->deprecated_at?->toDateString(),
            'endpoints' => [
                'links' => '/v1/links',
                'links_public' => '/v1/links/public',
                'domains' => '/v1/domains',
            ],
            'docs' => 'docs/api-v1-openapi.yaml',
        ]);
    }
}
