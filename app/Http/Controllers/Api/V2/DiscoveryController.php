<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\ApiVersion;
use App\Support\DailyLinkQuota;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiscoveryController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'version' => 2,
            'status' => ApiVersion::find(2)?->status->value ?? 'active',
            'latest_version' => ApiVersion::latestVersion(),
            'docs_url' => 'https://docs.ternis.link/api.md',
            'openapi_url' => 'https://docs.ternis.link/api-v2-openapi.yaml',
            'endpoints' => [
                'me' => '/v2/me',
                'domains' => '/v2/domains',
                'links' => '/v2/links',
                'clicks' => '/v2/links/{link}/clicks',
                'click_summary' => '/v2/links/{link}/clicks/summary',
                'stats' => '/v2/stats',
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $key = $request->attributes->get('api_key');

        return response()->json([
            'user_id' => $user->id,
            'auth_via' => $request->attributes->get('auth_via'),
            'api_key' => $key ? ['id' => $key->id, 'name' => $key->name, 'prefix' => $key->key_prefix] : null,
            'default_domain_id' => $user->resolvedDefaultDomain()?->id,
            'daily_link_quota' => DailyLinkQuota::forUser($user),
            'creation_rate_limit_per_minute' => $user->plan?->rate_limit_per_minute ?? 10,
            'request_rate_limit_per_minute' => 60,
        ]);
    }

    public function domains(Request $request): JsonResponse
    {
        return response()->json($request->user()->availableDomains()
            ->filter(fn ($domain) => $domain->isUsableForLinks())
            ->map(fn ($domain) => ['id' => $domain->id, 'hostname' => $domain->hostname])
            ->values());
    }
}
