<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reject cross-origin requests to the auth API whose Origin is not on
 * the CORS allowlist.
 *
 * The CORS headers alone only stop a browser from *reading* the
 * response — the request still reaches the server. This middleware
 * closes that gap: an origin that is not configured never gets an
 * answer, whatever the client is.
 *
 * Requests without an Origin header (server-to-server, curl) are left
 * alone: they carry no ambient cookie the browser would have attached
 * on its own, so the session stays the only credential.
 */
class EnsureCorsOrigin
{
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->headers->get('Origin');

        if (is_string($origin) && $origin !== '') {
            $allowed = (array) config('cors.allowed_origins', []);

            if (! in_array($origin, $allowed, true)) {
                return response()->json(['message' => 'Origin not allowed.'], 403);
            }
        }

        return $next($request);
    }
}
