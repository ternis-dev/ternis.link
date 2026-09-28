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
     * Overview: all-time totals + creations/clicks/QR per day (30d).
     */
    public function index()
    {
        $creations = NetworkStats::creationsByDay(30);
        $clicks = NetworkStats::clicksByDay(30);
        $qr = NetworkStats::qrByDay(30);

        return view('pages.stats.index', [
            'stats' => NetworkStats::overview(),
            'creationLabels' => collect($creations['labels']),
            'creationValues' => collect($creations['values']),
            'clickLabels' => collect($clicks['labels']),
            'clickValues' => collect($clicks['values']),
            'qrLabels' => collect($qr['labels']),
            'qrValues' => collect($qr['values']),
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
        return view('pages.stats.domains', [
            'domains' => $this->visibleDomains(),
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
    private function visibleDomains()
    {
        $userId = auth()->id();

        return NetworkStats::domains()
            ->filter(fn ($domain) => $domain->user_id === null || $domain->user_id === $userId)
            ->values();
    }
}
