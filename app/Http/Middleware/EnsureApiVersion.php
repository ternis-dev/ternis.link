<?php

namespace App\Http\Middleware;

use App\Enums\ApiVersionStatus;
use App\Models\ApiVersion;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforce API version lifecycle and expose version headers.
 *
 * - Retired versions return 410 Gone (JSON) before auth/throttle run.
 * - Deprecated versions add `Deprecation: true` + `Sunset` headers.
 * - All versioned responses carry `API-Version` + `API-Latest-Version`.
 *
 * Usage: ->middleware('ensure.api-version:1')
 */
class EnsureApiVersion
{
    public function handle(Request $request, Closure $next, int|string $version = 1): Response
    {
        $version = (int) $version;

        $record = Cache::remember(
            "api_version:{$version}",
            60,
            fn () => ApiVersion::where('version', $version)->first()
        );

        $latest = Cache::remember(
            'api_version:latest',
            60,
            fn () => ApiVersion::latestVersion()
        );

        if ($record && $record->status === ApiVersionStatus::Retired) {
            return response()->json([
                'message' => "API v{$version} is retired. Please migrate to v{$latest}.",
                'version' => $version,
                'latest_version' => $latest,
            ], 410, [
                'API-Version' => (string) $version,
                'API-Latest-Version' => (string) $latest,
            ]);
        }

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('API-Version', (string) $version);
        $response->headers->set('API-Latest-Version', (string) $latest);

        if ($record && $record->status === ApiVersionStatus::Deprecated) {
            $response->headers->set('Deprecation', 'true');

            if ($record->deprecated_at) {
                $response->headers->set(
                    'Sunset',
                    $record->deprecated_at->copy()->endOfDay()->toRfc7231String()
                );
            }
        }

        return $response;
    }
}
