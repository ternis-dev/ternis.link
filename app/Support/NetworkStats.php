<?php

namespace App\Support;

use App\Models\Click;
use App\Models\Domain;
use App\Models\Link;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Cached aggregate network stats for the public ternis.link pages
 * (HTML + Markdown twins) and machine-readable files (llms-full.txt).
 *
 * DSGVO by design: every number here is an aggregate (counts grouped
 * by day or domain). No personal data — no IPs, no user agents, no
 * referrers, no per-user rows — ever leaves the database. Raw rows
 * are never deleted (only IP ciphertext is pruned after 30 days),
 * so all-time totals stay complete forever.
 */
class NetworkStats
{
    /**
     * All-time totals + today's counts (cached 10 minutes).
     *
     * @return array{total_links: int, active_links: int, removed_links: int, total_clicks: int, links_today: int, clicks_today: int}
     */
    public static function overview(): array
    {
        return Cache::remember('stats:overview', 600, fn () => [
            'total_links' => Link::count(),
            'active_links' => Link::where('is_active', true)->where('is_removed', false)->count(),
            'removed_links' => Link::where('is_removed', true)->count(),
            'total_clicks' => Click::count(),
            'links_today' => Link::where('created_at', '>=', now()->startOfDay())->count(),
            'clicks_today' => Click::where('created_at', '>=', now()->startOfDay())->count(),
        ]);
    }

    /**
     * Zero-filled last N days, oldest first.
     *
     * @return Collection<int, array{date: string, label: string}>
     */
    public static function lastDays(int $days)
    {
        $out = collect();

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $out->push(['date' => $date->format('Y-m-d'), 'label' => $date->format('M j')]);
        }

        return $out;
    }

    /**
     * Links created per day, zero-filled.
     *
     * @return array{labels: list<string>, values: list<int>}
     */
    public static function creationsByDay(int $days = 30): array
    {
        $counts = Cache::remember('stats:creations-30d', 600, function () {
            return Link::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(29)->startOfDay())
                ->groupByRaw('DATE(created_at)')
                ->pluck('count', 'date')
                ->map(fn ($count) => (int) $count)
                ->all();
        });

        $labels = [];
        $values = [];

        foreach (self::lastDays($days) as $day) {
            $labels[] = $day['label'];
            $values[] = (int) ($counts[$day['date']] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Clicks per day, zero-filled.
     *
     * @return array{labels: list<string>, values: list<int>}
     */
    public static function clicksByDay(int $days = 30): array
    {
        $counts = Cache::remember('stats:clicks-30d', 600, function () {
            return Click::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(29)->startOfDay())
                ->groupByRaw('DATE(created_at)')
                ->pluck('count', 'date')
                ->map(fn ($count) => (int) $count)
                ->all();
        });

        $labels = [];
        $values = [];

        foreach (self::lastDays($days) as $day) {
            $labels[] = $day['label'];
            $values[] = (int) ($counts[$day['date']] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Links + clicks per domain, most links first (cached 10 minutes).
     */
    public static function domains(): Collection
    {
        return Cache::remember('stats:domains', 600, fn () => Domain::withCount('links')
            ->withSum('links', 'click_count')
            ->orderByDesc('links_count')
            ->get()
        );
    }

    /**
     * Most-clicked links — public slugs only, no owners, no targets
     * (cached 10 minutes).
     */
    public static function topLinks(int $limit = 50): Collection
    {
        return Cache::remember('stats:links', 600, fn () => Link::with('domain')
            ->orderByDesc('click_count')
            ->limit($limit)
            ->get(['id', 'slug', 'domain_id', 'click_count', 'is_active', 'is_removed', 'created_at'])
        );
    }
}
