<?php

namespace App\Support;

use App\Models\Click;
use App\Models\Domain;
use App\Models\Link;
use App\Models\LinkTombstone;
use App\Models\QrGeneration;
use Illuminate\Support\Arr;
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
 * so all-time totals stay complete forever. Hard-deleted links leave
 * a tombstone carrying their aggregates, merged in below.
 */
class NetworkStats
{
    /**
     * All-time totals + today's counts (cached 10 minutes).
     *
     * @return array{total_links: int, active_links: int, removed_links: int, total_clicks: int, links_today: int, clicks_today: int, direct_url_clicks: int, direct_url_clicks_today: int, qr_codes: int, qr_codes_today: int}
     */
    public static function overview(): array
    {
        return Cache::remember('stats:overview', 600, function () {
            $today = now()->startOfDay()->format('Y-m-d');

            return [
                'total_links' => Link::count() + LinkTombstone::count(),
                'active_links' => Link::where('is_active', true)->where('is_removed', false)->count(),
                'removed_links' => Link::where('is_removed', true)->count(),
                'total_clicks' => Click::count() + (int) LinkTombstone::sum('click_count'),
                'links_today' => Link::where('created_at', '>=', now()->startOfDay())->count()
                    + LinkTombstone::whereDate('created_day', $today)->count(),
                'clicks_today' => Click::where('created_at', '>=', now()->startOfDay())->count()
                    + self::tombstoneClicksOn($today),
                'direct_url_clicks' => Click::where('is_direct_url', true)->count(),
                'direct_url_clicks_today' => Click::where('is_direct_url', true)
                    ->where('created_at', '>=', now()->startOfDay())->count(),
                'qr_codes' => QrGeneration::count(),
                'qr_codes_today' => QrGeneration::where('created_at', '>=', now()->startOfDay())->count(),
            ];
        });
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
            $live = Link::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(29)->startOfDay())
                ->groupByRaw('DATE(created_at)')
                ->pluck('count', 'date')
                ->map(fn ($count) => (int) $count)
                ->all();

            $deleted = LinkTombstone::selectRaw('DATE(created_day) as date, COUNT(*) as count')
                ->where('created_day', '>=', now()->subDays(29)->startOfDay()->format('Y-m-d'))
                ->groupByRaw('DATE(created_day)')
                ->pluck('count', 'date')
                ->map(fn ($count) => (int) $count)
                ->all();

            foreach ($deleted as $date => $count) {
                $live[$date] = ($live[$date] ?? 0) + $count;
            }

            return $live;
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
            $live = Click::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(29)->startOfDay())
                ->groupByRaw('DATE(created_at)')
                ->pluck('count', 'date')
                ->map(fn ($count) => (int) $count)
                ->all();

            foreach (self::tombstoneClicksByDay() as $date => $count) {
                $live[$date] = ($live[$date] ?? 0) + $count;
            }

            return $live;
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
     * QR codes generated per day, zero-filled.
     *
     * @return array{labels: list<string>, values: list<int>}
     */
    public static function qrByDay(int $days = 30): array
    {
        $counts = Cache::remember('stats:qr-30d', 600, function () {
            return QrGeneration::selectRaw('DATE(created_at) as date, COUNT(*) as count')
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
     * Tombstoned links stay counted against their original hostname.
     * Visibility (platform vs. own domains) is decided per request in
     * StatsController — this query stays shared and cacheable.
     *
     * The cache holds plain attribute arrays, rehydrated via
     * Domain::hydrate() on every read: a cached Eloquent graph
     * unserializes as __PHP_Incomplete_Class when writer and reader
     * run different releases, which fatals the return type. Arrays
     * can't break that way.
     */
    public static function domains(): Collection
    {
        $rows = Cache::get('stats:domains:v2');

        if (! is_array($rows)) {
            // Anything that isn't attribute rows (e.g. a payload cached
            // by another release) is discarded and rebuilt transparently
            // instead of fataling the return type on unserialize.
            Cache::forget('stats:domains:v2');
            $rows = self::buildDomainRows();
            Cache::put('stats:domains:v2', $rows, 600);
        }

        return Domain::hydrate($rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function buildDomainRows(): array
    {
        $domains = Domain::withCount('links')
            ->withSum('links', 'click_count')
            ->orderByDesc('links_count')
            ->get();

        $tombstones = LinkTombstone::selectRaw('domain_hostname, COUNT(*) as links, SUM(click_count) as clicks')
            ->groupBy('domain_hostname')
            ->get()
            ->keyBy('domain_hostname');

        foreach ($domains as $domain) {
            $tomb = $tombstones->get($domain->hostname);
            $domain->links_count += (int) ($tomb?->links ?? 0);
            $domain->links_sum_click_count = ($domain->links_sum_click_count ?? 0) + (int) ($tomb?->clicks ?? 0);
        }

        return $domains->sortByDesc('links_count')->values()
            ->map(fn (Domain $domain) => Arr::except($domain->getAttributes(), ['verification_token']))
            ->all();
    }

    /**
     * Drop every cached aggregate (called after hard deletes so the
     * preserved tombstone counts show up immediately).
     */
    public static function flush(): void
    {
        foreach (['stats:overview', 'stats:creations-30d', 'stats:clicks-30d', 'stats:qr-30d', 'stats:domains', 'stats:domains:v2'] as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Tombstoned clicks merged per day: [Y-m-d => count].
     */
    private static function tombstoneClicksByDay(): array
    {
        $merged = [];

        foreach (LinkTombstone::whereNotNull('clicks_by_day')->pluck('clicks_by_day') as $perDay) {
            foreach ((array) $perDay as $date => $count) {
                $merged[$date] = ($merged[$date] ?? 0) + (int) $count;
            }
        }

        return $merged;
    }

    private static function tombstoneClicksOn(string $day): int
    {
        return (int) (self::tombstoneClicksByDay()[$day] ?? 0);
    }
}
