<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceDomainAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $domainType = $request->attributes->get('domain_type');
        $authRequired = config("domains.auth_required.{$domainType}", false);

        if (! $authRequired) {
            return $next($request);
        }

        if (! $request->user()) {
            return redirect()->route('login');
        }

        // Admin domain requires admin role
        if ($domainType === 'admin' && ! $request->user()->isAdmin()) {
            abort(403, 'Admin access required.');
        }

        // Ternis domain requires family, partner, or admin role
        if ($domainType === 'ternis') {
            $role = $request->user()->role;
            if (! in_array($role, [UserRole::Admin, UserRole::Family, UserRole::Partner])) {
                abort(403, 'Ternis family or partner access required.');
            }
        }

        // Business domain requires admin role
        if ($domainType === 'business' && ! $request->user()->isAdmin()) {
            abort(403, 'Business access required.');
        }

        return $next($request);
    }
}
