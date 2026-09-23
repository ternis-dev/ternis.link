<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fail fast when a route is hit on the wrong host type.
 *
 * Relies on ResolveDomain (global middleware) having set the
 * `domain_type` request attribute. Use with parameters, e.g.:
 *
 *   ->middleware('ensure.domain:api')
 *   ->middleware('ensure.domain:dashboard,admin')
 *   ->middleware('ensure.domain:public,business,ternis,partner')
 *
 * Unknown types 404 so we never leak the existence of host-pinned
 * routes (dashboard on href.nz, API CRUD on redirect domains, …).
 * The localhost/testserver fallback in ResolveDomain already maps
 * dev URLs to dashboard/api/public types, so local development keeps
 * working without special-casing here.
 */
class EnsureDomainType
{
    /**
     * @param  string  ...$allowed  Allowed domain_type values (commas also split).
     */
    public function handle(Request $request, Closure $next, string ...$allowed): Response
    {
        $flat = [];

        foreach ($allowed as $entry) {
            foreach (explode(',', $entry) as $part) {
                $part = trim($part);

                if ($part !== '') {
                    $flat[] = $part;
                }
            }
        }

        $type = $request->attributes->get('domain_type');

        if (! in_array($type, $flat, true)) {
            abort(404);
        }

        return $next($request);
    }
}
