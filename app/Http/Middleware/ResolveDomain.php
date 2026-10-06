<?php

namespace App\Http\Middleware;

use App\Models\Domain;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveDomain
{
    /**
     * Resolve the incoming hostname to a domain type and optional Domain model.
     * Sets request attributes: domain_type, domain_model, domain_hostname.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Health probes must answer on any Host/IP (load balancers hit
        // pod IPs directly). Bypass domain resolution entirely — this
        // is defense in depth alongside withoutMiddleware() on the route.
        if (in_array($request->path(), ['healthz', 'up'], true)) {
            return $next($request);
        }

        $rawHost = $request->header('Host') ?? $request->header('X-Forwarded-Host') ?? $request->getHost();
        $hostname = explode(':', $rawHost)[0];

        $domainMap = config('domains.map', []);
        $wildcardRoots = config('domains.wildcard_roots', []);

        // The QR host is configurable and also has dedicated routes. Keep
        // it resolvable even when a deployment's cached domain map predates
        // the QR host entry.
        $qrHost = (string) config('domains.qr_host', 'qr.href.nz');
        if ($hostname === $qrHost) {
            if (! in_array($request->path(), ['', '/', 'new'], true)) {
                $apiHost = (string) config('domains.qr_api_host', 'qr.t-api.de');

                return redirect()->away($request->getScheme().'://'.$apiHost.$request->getRequestUri(), 301);
            }

            $request->attributes->set('domain_type', 'public');
            $request->attributes->set('domain_hostname', $hostname);
            $request->attributes->set('domain_model', Domain::where('hostname', $hostname)->first());

            return $next($request);
        }

        // Redirect api.ternis.link to links.t-api.de
        if ($hostname === 'api.ternis.link') {
            return redirect()->away('https://links.t-api.de'.$request->getRequestUri(), 301);
        }

        // All *.t-api.de subdomains and apex t-api.de are API domains
        if ($hostname === 't-api.de' || str_ends_with($hostname, '.t-api.de')) {
            $request->attributes->set('domain_type', 'api');
            $request->attributes->set('domain_hostname', $hostname);
            $domain = Domain::where('hostname', $hostname)->first();
            $request->attributes->set('domain_model', $domain);

            return $next($request);
        }

        // Redirect www.<apex> to the bare apex host (e.g.
        // www.href.nz → href.nz), preserving scheme and URI.
        if (str_starts_with($hostname, 'www.')) {
            $apex = substr($hostname, 4);

            if (in_array($apex, (array) config('domains.www_redirect', []), true)) {
                return redirect()->away($request->getScheme().'://'.$apex.$request->getRequestUri(), 301);
            }
        }

        // Public dashboard legacy hosts: my.href.nz / my.href.yt now 302
        // to the canonical my.ternis.link (shared ternis.link session).
        // Temporary (302, not 301) while the move settles.
        $publicDash = (string) config('domains.public_dashboard_host', 'my.ternis.link');
        $legacyDashHosts = (array) config('domains.public_dashboard_legacy_hosts', ['my.href.nz', 'my.href.yt']);
        if (in_array($hostname, $legacyDashHosts, true)) {
            return redirect()->away($request->getScheme().'://'.$publicDash.$request->getRequestUri(), 302);
        }

        // 1. Direct match in config
        if (isset($domainMap[$hostname])) {
            $request->attributes->set('domain_type', $domainMap[$hostname]);
            $request->attributes->set('domain_hostname', $hostname);

            $domain = Domain::where('hostname', $hostname)->first();
            $request->attributes->set('domain_model', $domain);

            return $next($request);
        }

        // 2. Wildcard subdomain match (e.g., "foo.ternis.link")
        foreach ($wildcardRoots as $root) {
            if (str_ends_with($hostname, ".{$root}")) {
                $domain = Domain::where('hostname', $hostname)
                    ->where('is_active', true)
                    ->first();

                if ($domain) {
                    $request->attributes->set('domain_type', $domain->type->value ?? (string) $domain->type);
                    $request->attributes->set('domain_hostname', $hostname);
                    $request->attributes->set('domain_model', $domain);

                    return $next($request);
                }

                abort(404, "Unknown subdomain: {$hostname}");
            }
        }

        // 3. Check domains table for partner custom domains
        $domain = Domain::where('hostname', $hostname)
            ->where('is_active', true)
            ->first();

        if ($domain) {
            $request->attributes->set('domain_type', $domain->type->value ?? (string) $domain->type);
            $request->attributes->set('domain_hostname', $hostname);
            $request->attributes->set('domain_model', $domain);

            return $next($request);
        }

        // 4. Localhost / testserver dev fallback
        if (in_array($hostname, ['localhost', '127.0.0.1', '::1', 'testserver'])) {
            if ($request->is('admin*')) {
                $request->attributes->set('domain_type', 'admin');
                $request->attributes->set('domain_hostname', 'admin.ternis.link');
                $request->attributes->set('domain_model', Domain::where('hostname', 'ternis.link')->first());

                return $next($request);
            }

            if ($request->is('dashboard*') || $request->is('login*') || $request->is('auth*')) {
                $request->attributes->set('domain_type', 'dashboard');
                $request->attributes->set('domain_hostname', 'dash.ternis.link');
                $request->attributes->set('domain_model', Domain::where('hostname', 'ternis.link')->first());

                return $next($request);
            }

            // New root dashboard URLs (no /dashboard prefix). Localhost
            // serves them like the legacy /dashboard/* paths above; /
            // intentionally stays public (landing) here.
            if ($request->is('links*') || $request->is('new') || $request->is('api-keys*') || $request->is('domains*') || $request->is('settings*') || $request->is('bio*')) {
                $request->attributes->set('domain_type', 'dashboard');
                $request->attributes->set('domain_hostname', 'dash.ternis.link');
                $request->attributes->set('domain_model', Domain::where('hostname', 'ternis.link')->first());

                return $next($request);
            }

            if ($request->is('v1*')) {
                $request->attributes->set('domain_type', 'api');
                $request->attributes->set('domain_hostname', 'links.t-api.de');

                return $next($request);
            }

            $defaultPublicDomain = Domain::where('hostname', 'href.nz')->first();
            $request->attributes->set('domain_type', 'public');
            $request->attributes->set('domain_hostname', 'href.nz');
            $request->attributes->set('domain_model', $defaultPublicDomain);

            return $next($request);
        }

        abort(404, "Unknown domain: {$hostname}");
    }
}
