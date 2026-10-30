<?php

namespace App\Http\Controllers;

use App\Support\NetworkStats;

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
    public function index()
    {
        $creations = NetworkStats::creationsByDomain(30);
        $clicks = NetworkStats::clicksByDomain(30);
        $qr = NetworkStats::qrByDomain(30);

        return view('pages.stats.index', [
            'stats' => NetworkStats::overview(),
            'heatmaps' => NetworkStats::heatmaps(),
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
    public function domains()
    {
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
    public function activity()
    {
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

    public function security()
    {
        return view('pages.stats.security', ['stats' => NetworkStats::overview()]);
    }

    public function securityMd()
    {
        return response()->view('pages.stats.security-md', ['stats' => NetworkStats::overview()], 200, ['Content-Type' => self::MARKDOWN]);
    }

    private function visibleDomains()
    {
        $userId = auth()->id();

        return NetworkStats::domains()
            ->filter(fn ($domain) => $domain->user_id === null || $domain->user_id === $userId)
            ->values();
    }
}
