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
     * Overview: all-time totals + creations/clicks per day (30d).
     */
    public function index()
    {
        $creations = NetworkStats::creationsByDay(30);
        $clicks = NetworkStats::clicksByDay(30);

        return view('pages.stats.index', [
            'stats' => NetworkStats::overview(),
            'creationLabels' => collect($creations['labels']),
            'creationValues' => collect($creations['values']),
            'clickLabels' => collect($clicks['labels']),
            'clickValues' => collect($clicks['values']),
        ]);
    }

    /**
     * Markdown twin of the overview (GET /pages/stats.md).
     */
    public function indexMd()
    {
        $creations = NetworkStats::creationsByDay(30);
        $clicks = NetworkStats::clicksByDay(30);

        return response()->view('pages.stats.index-md', [
            'stats' => NetworkStats::overview(),
            'creationLabels' => $creations['labels'],
            'creationValues' => $creations['values'],
            'clickLabels' => $clicks['labels'],
            'clickValues' => $clicks['values'],
        ], 200, ['Content-Type' => self::MARKDOWN]);
    }

    /**
     * Links per domain — public (user-added) domains only, full table.
     */
    public function domains()
    {
        return view('pages.stats.domains', [
            'domains' => NetworkStats::publicDomains(),
        ]);
    }

    /**
     * Markdown twin of the domain table (GET /pages/stats/domains.md).
     */
    public function domainsMd()
    {
        return response()->view('pages.stats.domains-md', [
            'domains' => NetworkStats::publicDomains(),
        ], 200, ['Content-Type' => self::MARKDOWN]);
    }
}
