<?php

namespace App\Http\Controllers;

use App\Support\NetworkStats;
use Illuminate\Http\Request;

/**
 * Public network stats on the ternis host (ternis.link/pages/stats).
 *
 * Every page has a Markdown twin ({uri}.md, text/markdown) for
 * crawlers, agents, and llms-full.txt — same aggregates, no chrome.
 * See Privacy note on NetworkStats: aggregates only, no personal data.
 */
class StatsController extends Controller
{
    public const MARKDOWN = 'text/markdown; charset=UTF-8';

    /**
     * Overview: all-time totals + heatmaps + creations/clicks/QR per day (30d).
     */
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->query('format') === 'json') {
            return $this->data($request, 'overview');
        }

        $creations = NetworkStats::creationsByDomain(30);
        $clicks = NetworkStats::clicksByDomain(30);
        $qr = NetworkStats::qrByDomain(30);
        $heatmaps = NetworkStats::heatmaps();

        return view('pages.stats.index', [
            'stats' => NetworkStats::overview(),
            'heatmaps' => $heatmaps,
            'heatmapSummaries' => NetworkStats::heatmapSummaries($heatmaps),
            'creationLabels' => collect($creations['labels']),
            'creationValues' => collect($creations['values']),
            'creationDatasets' => $creations['datasets'],
            'clickLabels' => collect($clicks['labels']),
            'clickValues' => collect($clicks['values']),
            'clickDatasets' => $clicks['datasets'],
            'qrLabels' => collect($qr['labels']),
            'qrValues' => collect($qr['values']),
            'qrDatasets' => $qr['datasets'],
        ]);
    }

    /**
     * Markdown twin of the overview (GET /pages/stats.md).
     */
    public function indexMd()
    {
        $creations = NetworkStats::creationsByDay(30);
        $clicks = NetworkStats::clicksByDay(30);
        $qr = NetworkStats::qrByDay(30);

        return response()->view('pages.stats.index-md', [
            'stats' => NetworkStats::overview(),
            'heatmaps' => NetworkStats::heatmaps(),
            'creationLabels' => $creations['labels'],
            'creationValues' => $creations['values'],
            'clickLabels' => $clicks['labels'],
            'clickValues' => $clicks['values'],
            'qrLabels' => $qr['labels'],
            'qrValues' => $qr['values'],
        ], 200, ['Content-Type' => self::MARKDOWN]);
    }

    /**
     * Links per domain — platform domains for everyone, plus the
     * signed-in user's own custom domains. Other users' hostnames are
     * never listed publicly.
     */
    public function domains(Request $request)
    {
        if ($request->wantsJson() || $request->query('format') === 'json') {
            return $this->data($request, 'domains');
        }

        $domains = $this->visibleDomains();
        $domainColors = $domains->mapWithKeys(fn ($domain) => [
            $domain->hostname => NetworkStats::domainColor($domain->hostname),
        ])->all();

        return view('pages.stats.domains', [
            'domains' => $domains,
            'domainColors' => $domainColors,
        ]);
    }

    /**
     * Markdown twin of the domain table (GET /pages/stats/domains.md).
     */
    public function domainsMd()
    {
        return response()->view('pages.stats.domains-md', [
            'domains' => $this->visibleDomains(),
        ], 200, ['Content-Type' => self::MARKDOWN]);
    }

    /**
     * Built-in system domains plus the current user's own. Auth state
     * is request-specific, so this filters outside the shared cached
     * query in NetworkStats::domains().
     */
    public function activity(Request $request)
    {
        if ($request->wantsJson() || $request->query('format') === 'json') {
            return $this->data($request, 'activity');
        }

        return view('pages.stats.activity', $this->activityData());
    }

    public function activityMd()
    {
        return response()->view('pages.stats.activity-md', $this->activityData(), 200, ['Content-Type' => self::MARKDOWN]);
    }

    private function activityData(): array
    {
        return ['links' => NetworkStats::creationsByDay(), 'clicks' => NetworkStats::clicksByDay(), 'qr' => NetworkStats::qrByDay()];
    }

    public function security(Request $request)
    {
        if ($request->wantsJson() || $request->query('format') === 'json') {
            return $this->data($request, 'security');
        }

        return view('pages.stats.security', [
            'stats' => NetworkStats::overview(),
            'breakdown' => NetworkStats::securityBreakdown(),
        ]);
    }

    public function securityMd()
    {
        return response()->view('pages.stats.security-md', [
            'stats' => NetworkStats::overview(),
            'breakdown' => NetworkStats::securityBreakdown(),
        ], 200, ['Content-Type' => self::MARKDOWN]);
    }

    /**
     * Web API endpoint returning cached aggregate stats as JSON.
     *
     * Supports ETag / 304 Not Modified validation, user-type tailored poll intervals,
     * and optional ?section= query (overview, activity, security, domains, heatmaps).
     */
    public function data(Request $request, ?string $section = null)
    {
        $section = $section ?? $request->query('section', 'all');

        $user = auth()->user();
        $userType = $user ? ($user->isAdmin() ? 'admin' : 'user') : 'guest';
        $pollInterval = (int) config("stats.poll_interval.{$userType}", 45);
        $cacheTtl = (int) config('stats.overview_ttl', 45);

        $creations = NetworkStats::creationsByDomain(30);
        $clicks = NetworkStats::clicksByDomain(30);
        $qr = NetworkStats::qrByDomain(30);

        $visibleDomains = $this->visibleDomains();
        $domainList = $visibleDomains->map(fn ($domain) => [
            'hostname' => $domain->hostname,
            'links_count' => (int) $domain->links_count,
            'links_sum_click_count' => (int) ($domain->links_sum_click_count ?? 0),
            'is_custom' => $domain->user_id !== null,
            'color' => NetworkStats::domainColor($domain->hostname),
        ])->values()->all();

        $heatmaps = NetworkStats::heatmaps();
        $heatmapSummaries = NetworkStats::heatmapSummaries($heatmaps);

        $overview = NetworkStats::overview();
        $activity = $this->activityData();

        $payload = match ($section) {
            'overview' => [
                'overview' => $overview,
                'charts' => [
                    'creations' => $creations,
                    'clicks' => $clicks,
                    'qr' => $qr,
                ],
                'heatmaps' => [
                    'calendar' => $heatmaps['calendar'],
                    'hourly' => $heatmaps['hourly'],
                    'summaries' => $heatmapSummaries,
                ],
            ],
            'activity' => [
                'activity' => $activity,
                'charts' => [
                    'creations' => $creations,
                    'clicks' => $clicks,
                    'qr' => $qr,
                ],
            ],
            'security' => [
                'overview' => $overview,
                'breakdown' => NetworkStats::securityBreakdown(),
            ],
            'domains' => [
                'domains' => $domainList,
            ],
            default => [
                'overview' => $overview,
                'security' => [
                    'overview' => $overview,
                    'breakdown' => NetworkStats::securityBreakdown(),
                ],
                'charts' => [
                    'creations' => $creations,
                    'clicks' => $clicks,
                    'qr' => $qr,
                ],
                'activity' => $activity,
                'domains' => $domainList,
                'heatmaps' => [
                    'calendar' => $heatmaps['calendar'],
                    'hourly' => $heatmaps['hourly'],
                    'summaries' => $heatmapSummaries,
                ],
            ],
        };

        $payload['meta'] = [
            'section' => $section,
            'user_type' => $userType,
            'poll_interval' => $pollInterval,
            'cache_ttl' => $cacheTtl,
            'timestamp' => now()->toIso8601String(),
        ];

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $etag = '"'.md5($json).'"';

        if ($request->headers->get('If-None-Match') === $etag) {
            return response('', 304, [
                'ETag' => $etag,
                'Cache-Control' => 'private, must-revalidate, max-age=15',
            ]);
        }

        return response($json, 200, [
            'Content-Type' => 'application/json',
            'ETag' => $etag,
            'Cache-Control' => 'private, must-revalidate, max-age=15',
        ]);
    }

    private function visibleDomains()
    {
        $userId = auth()->id();

        return NetworkStats::domains()
            ->filter(fn ($domain) => $domain->user_id === null || $domain->user_id === $userId)
            ->values();
    }
}
