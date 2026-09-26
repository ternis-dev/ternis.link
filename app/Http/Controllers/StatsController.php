<?php

namespace App\Http\Controllers;

use App\Models\Click;
use App\Models\Domain;
use App\Models\Link;
use Illuminate\Support\Facades\Cache;

/**
 * Public network stats on the ternis host (ternis.link/stats).
 *
 * DSGVO by design: every number here is an aggregate (counts grouped
 * by day or domain). No personal data — no IPs, no user agents, no
 * referrers, no per-user rows — ever leaves the database for these
 * pages. Raw rows are never deleted (only IP ciphertext is pruned
 * after 30 days), so all-time totals stay complete forever.
 */
class StatsController extends Controller
{
    /**
     * Overview: all-time totals + creations/clicks per day (30d).
     */
    public function index()
    {
        $stats = Cache::remember('stats:overview', 600, fn () => [
            'total_links' => Link::count(),
            'active_links' => Link::where('is_active', true)->where('is_removed', false)->count(),
            'removed_links' => Link::where('is_removed', true)->count(),
            'total_clicks' => Click::count(),
            'links_today' => Link::where('created_at', '>=', now()->startOfDay())->count(),
            'clicks_today' => Click::where('created_at', '>=', now()->startOfDay())->count(),
        ]);

        $days = $this->lastDays(30);

        $creations = Cache::remember('stats:creations-30d', 600, function () {
            return Link::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(29)->startOfDay())
                ->groupByRaw('DATE(created_at)')
                ->pluck('count', 'date')
                ->map(fn ($count) => (int) $count)
                ->all();
        });

        $clicks = Cache::remember('stats:clicks-30d', 600, function () {
            return Click::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(29)->startOfDay())
                ->groupByRaw('DATE(created_at)')
                ->pluck('count', 'date')
                ->map(fn ($count) => (int) $count)
                ->all();
        });

        return view('pages.stats.index', [
            'stats' => $stats,
            'creationLabels' => $days->pluck('label'),
            'creationValues' => $days->map(fn ($day) => (int) ($creations[$day['date']] ?? 0)),
            'clickLabels' => $days->pluck('label'),
            'clickValues' => $days->map(fn ($day) => (int) ($clicks[$day['date']] ?? 0)),
        ]);
    }

    /**
     * Links per domain — full table.
     */
    public function domains()
    {
        $domains = Cache::remember('stats:domains', 600, fn () => Domain::withCount('links')
            ->withSum('links', 'click_count')
            ->orderByDesc('links_count')
            ->get()
        );

        return view('pages.stats.domains', compact('domains'));
    }

    /**
     * Most-clicked links — public slugs only, no owners, no targets.
     */
    public function links()
    {
        $links = Cache::remember('stats:links', 600, fn () => Link::with('domain')
            ->orderByDesc('click_count')
            ->limit(50)
            ->get(['id', 'slug', 'domain_id', 'click_count', 'is_active', 'is_removed', 'created_at'])
        );

        return view('pages.stats.links', compact('links'));
    }

    /**
     * Zero-filled last N days, oldest first.
     *
     * @return \Illuminate\Support\Collection<int, array{date: string, label: string}>
     */
    private function lastDays(int $days)
    {
        $out = collect();

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $out->push(['date' => $date->format('Y-m-d'), 'label' => $date->format('M j')]);
        }

        return $out;
    }
}
