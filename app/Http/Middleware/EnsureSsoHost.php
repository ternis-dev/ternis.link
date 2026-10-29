<?php

namespace App\Http\Middleware;

use App\Support\PublicDashboard;
use App\Support\SsoHosts;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** 404 unless the request came in on a linked (config/domains.php) host. */
class EnsureSsoHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! SsoHosts::isAllowed($request->getHost()) && ! in_array($request->getHost(), PublicDashboard::hosts(), true)) {
            abort(404);
        }

        return $next($request);
    }
}
