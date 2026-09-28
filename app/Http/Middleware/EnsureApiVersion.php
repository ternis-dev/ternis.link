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

        // Scalars only: config cache.serializable_classes=false means
        // cached Eloquent models come back as __PHP_Incomplete_Class.
        $meta = Cache::remember(
            "api_version:{$version}:meta",
            60,
            function () use ($version) {
                $record = ApiVersion::where('version', $version)->first();

                if (! $record) {
                    return null;
                }

                return [
                    'status' => $record->status->value,
                    'sunset' => $record->deprecated_at?->copy()->endOfDay()->toRfc7231String(),
                ];
            }
        );

        $latest = Cache::remember(
            'api_version:latest',
            60,
            fn () => ApiVersion::latestVersion()
        );

        if ($meta !== null && $meta['status'] === ApiVersionStatus::Retired->value) {
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

        if ($meta !== null && $meta['status'] === ApiVersionStatus::Deprecated->value) {
            $response->headers->set('Deprecation', 'true');

            if (! empty($meta['sunset'])) {
                $response->headers->set('Sunset', $meta['sunset']);
            }
        }

        return $response;
    }
}
