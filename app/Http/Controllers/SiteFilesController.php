<?php

namespace App\Http\Controllers;

use App\Support\NetworkStats;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Machine-readable site files, served as REAL routes (not static
 * files) so content stays correct per host and stays in sync with
 * the app. Host-blind by design — crawlers fetch these per hostname —
 * using the global `domain_type` attribute for per-host policy.
 *
 * IMPORTANT: these routes must stay registered BEFORE the short-link
 * catch-all (/{input}) at the bottom of routes/web.php, otherwise
 * /sitemap.xml etc. would be swallowed as link slugs on the
 * short-link hosts. /robots.txt must also NOT exist as a static file
 * in public/ (the web server would serve it before Laravel runs).
 *
 * GET /robots.txt      — curated crawl policy + Sitemap pointer
 * GET /sitemap.xml     — same-host app pages only (no user short links:
 *                        unbounded user content must never enter a sitemap)
 * GET /llms.txt        — service summary + page inventory (llmstxt.org)
 * GET /llms-full.txt   — everything inline: legal texts, API, snapshot
 */
class SiteFilesController extends Controller
{
    public const MARKDOWN = 'text/markdown; charset=UTF-8';

    public const PLAIN = 'text/plain; charset=UTF-8';

    public const XML = 'application/xml; charset=UTF-8';

    public function robots(Request $request): Response
    {
        $type = $request->attributes->get('domain_type');

        // App surfaces (dashboard, admin, API, unknown): nothing to crawl.
        if (! in_array($type, ['public', 'business', 'ternis', 'partner'], true)) {
            return response("User-agent: *\nDisallow: /\n", 200, ['Content-Type' => self::PLAIN]);
        }

        // Short-link hosts: the homepage (and /pages/ on the ternis
        // host) may be crawled; everything else — auth, redirect
        // endpoints, and the infinite /{slug} space — stays out.
        // Longest-match wins, so the specific Allows beat Disallow: /.
        $lines = [
            'User-agent: *',
            'Allow: /$',
            'Allow: /pages/',
            'Disallow: /auth/',
            'Disallow: /login',
            'Disallow: /logout',
            'Disallow: /url/',
            'Disallow: /go/',
            'Disallow: /preview/',
            'Disallow: /dashboard',
            'Disallow: /admin',
            'Disallow: /api/',
            'Disallow: /',
            'Sitemap: '.$request->getSchemeAndHttpHost().'/sitemap.xml',
            '',
        ];

        return response(implode("\n", $lines), 200, ['Content-Type' => self::PLAIN]);
    }

    public function sitemap(Request $request): Response
    {
        $type = $request->attributes->get('domain_type');
        $base = $request->getSchemeAndHttpHost();
        $today = now()->toDateString();

        $urls = [['loc' => $base.'/', 'lastmod' => $today]];

        // /pages/* exists on the ternis host only — a sitemap must
        // never link off-host, so other hosts list just their homepage.
        if ($type === 'ternis') {
            $urls[] = ['loc' => $base.'/pages/stats', 'lastmod' => $today];
            $urls[] = ['loc' => $base.'/pages/stats/domains', 'lastmod' => $today];
            $urls[] = ['loc' => $base.'/pages/stats/links', 'lastmod' => $today];

            foreach (PagesController::PAGES as $slug => $title) {
                $path = resource_path("legal/{$slug}.md");
                $urls[] = [
                    'loc' => $base."/pages/legal/{$slug}",
                    'lastmod' => is_file($path) ? date('Y-m-d', (int) filemtime($path)) : $today,
                ];
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            $xml .= '  <url><loc>'.e($url['loc']).'</loc><lastmod>'.$url['lastmod'].'</lastmod></url>'."\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => self::XML]);
    }

    public function llms(Request $request): Response
    {
        return response()->view('site.llms', $this->llmsData($request), 200, [
            'Content-Type' => self::MARKDOWN,
        ]);
    }

    public function llmsFull(Request $request): Response
    {
        return response()->view('site.llms-full', $this->llmsData($request)
            + ['overview' => NetworkStats::overview()]
            + ['legal' => $this->legalTexts()], 200, [
                'Content-Type' => self::MARKDOWN,
            ]);
    }

    /**
     * Shared view data: request base for self-links, canonical https
     * hosts for cross-links (/pages/* only resolves on ternis.link —
     * linking it off the request host would 404 on other hosts).
     */
    private function llmsData(Request $request): array
    {
        $host = fn (string $key, string $fallback) => 'https://'.(config("domains.{$key}") ?: $fallback);

        return [
            'base' => $request->getSchemeAndHttpHost(),
            'hosts' => [
                'public' => $host('public_host', 'href.nz'),
                'business' => $host('business_host', 'href.re'),
                'ternis' => 'https://ternis.link',
                'dashboard' => $host('dashboard_host', 'dash.ternis.link'),
                'admin' => $host('admin_host', 'admin.ternis.link'),
                'api' => 'https://links.t-api.de',
            ],
            'pages' => PagesController::PAGES,
            'redirects' => PagesController::REDIRECTS,
        ];
    }

    /**
     * @return array<string, string> slug => raw markdown source
     */
    private function legalTexts(): array
    {
        $out = [];

        foreach (array_keys(PagesController::PAGES) as $slug) {
            $path = resource_path("legal/{$slug}.md");
            $out[$slug] = is_file($path) ? (string) file_get_contents($path) : '';
        }

        return $out;
    }
}
