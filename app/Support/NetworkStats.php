<?php

namespace App\Support;

use App\Models\Click;
use App\Models\Domain;
use App\Models\Link;
use App\Models\LinkTombstone;
use App\Models\QrGeneration;
use App\Models\ThreatEvaluation;
use App\Services\LinkInsights;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Cached aggregate network stats for the public ternis.link pages
 * (HTML + Markdown twins) and machine-readable files (llms-full.txt).
 *
 * DSGVO by design: every number here is an aggregate (counts grouped
 * by day, weekday/hour, or domain). No personal data — no IPs, no user
 * agents, no referrers, no per-user rows — ever leaves the database.
 * Raw rows are never deleted (only IP ciphertext is pruned after 30
 * days), so all-time totals stay complete forever. Hard-deleted links
 * leave a tombstone carrying their aggregates, merged in below.
 */
class NetworkStats
{
    /**
     * Distinct brand color pairs (light / dark hex) per platform domain.
     *
     * @var array<string, array{light: string, dark: string}>
     */
    private const DOMAIN_COLORS = [
        'href.nz' => ['light' => '#7c3aed', 'dark' => '#a78bfa'],
        'ternis.link' => ['light' => '#059669', 'dark' => '#34d399'],
        'meinlink.at' => ['light' => '#e11d48', 'dark' => '#fb7185'],
        'clicked.at' => ['light' => '#4f46e5', 'dark' => '#818cf8'],
        'qr.href.nz' => ['light' => '#0891b2', 'dark' => '#22d3ee'],
        'qr.href.nz / Direct QR' => ['light' => '#0891b2', 'dark' => '#22d3ee'],
        'href.yt' => ['light' => '#dc2626', 'dark' => '#f87171'],
        'href.re' => ['light' => '#d97706', 'dark' => '#fbbf24'],
        'int.ternis.link' => ['light' => '#0284c7', 'dark' => '#38bdf8'],
        'links.thosted.de' => ['light' => '#0d9488', 'dark' => '#2dd4bf'],
        'short.thosted.de' => ['light' => '#ea580c', 'dark' => '#fb923c'],
        'go.thosted.de' => ['light' => '#65a30d', 'dark' => '#a3e635'],
        'go.ternis.net' => ['light' => '#2563eb', 'dark' => '#60a5fa'],
        'go.ternis.dev' => ['light' => '#c026d3', 'dark' => '#e879f9'],
        'go.ternis.org' => ['light' => '#db2777', 'dark' => '#f472b6'],
        'go.ternis.eu' => ['light' => '#ca8a04', 'dark' => '#facc15'],
        'Custom domains' => ['light' => '#9333ea', 'dark' => '#d8b4fe'],
    ];

    /**
     * Fallback vibrant color pairs hashed deterministically for custom domains.
     *
     * @var list<array{light: string, dark: string}>
     */
    private const FALLBACK_DOMAIN_COLORS = [
        ['light' => '#2563eb', 'dark' => '#60a5fa'],
        ['light' => '#059669', 'dark' => '#34d399'],
        ['light' => '#d97706', 'dark' => '#fbbf24'],
        ['light' => '#7c3aed', 'dark' => '#a78bfa'],
        ['light' => '#e11d48', 'dark' => '#fb7185'],
        ['light' => '#0891b2', 'dark' => '#22d3ee'],
        ['light' => '#ea580c', 'dark' => '#fb923c'],
        ['light' => '#4f46e5', 'dark' => '#818cf8'],
        ['light' => '#0d9488', 'dark' => '#2dd4bf'],
        ['light' => '#c026d3', 'dark' => '#e879f9'],
        ['light' => '#65a30d', 'dark' => '#a3e635'],
        ['light' => '#dc2626', 'dark' => '#f87171'],
    ];

    /**
     * Resolve a distinct light/dark color pair for any domain label.
     *
     * @return array{light: string, dark: string}
     */
    public static function domainColor(string $hostname): array
    {
        if (isset(self::DOMAIN_COLORS[$hostname])) {
            return self::DOMAIN_COLORS[$hostname];
        }

        $idx = abs(crc32(strtolower($hostname))) % count(self::FALLBACK_DOMAIN_COLORS);

        return self::FALLBACK_DOMAIN_COLORS[$idx];
    }

    /**
     * All-time totals + today's counts (cached 10 minutes).
     *
     * @return array{total_links: int, active_links: int, removed_links: int, total_clicks: int, links_today: int, clicks_today: int, direct_url_clicks: int, direct_url_clicks_today: int, qr_codes: int, qr_codes_today: int}
     */
    public static function overview(): array
    {
        return Cache::remember('stats:overview:v2', (int) config('stats.overview_ttl', 45), function () {
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
                'threat_evaluations' => ThreatEvaluation::count(),
                'threat_evaluations_today' => ThreatEvaluation::where('created_at', '>=', now()->startOfDay())->count(),
                'threats_quarantined' => ThreatEvaluation::where('action_taken', 'quarantined')->count()
                    + Link::whereJsonContains('tags', 'quarantine')
                        ->whereNotIn('id', ThreatEvaluation::where('action_taken', 'quarantined')->whereNotNull('link_id')->select('link_id'))
                        ->count(),
                'threats_bot_protected' => ThreatEvaluation::where('action_taken', 'bot_protected')->count(),
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
        $counts = Cache::remember('stats:creations-30d', (int) config('stats.charts_ttl', 60), function () {
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
     * Links created per day broken down by platform domain (+ aggregate
     * "Custom domains" bucket so private hostnames are never exposed).
     *
     * @return array{labels: list<string>, values: list<int>, datasets: list<array<string, mixed>>}
     */
    public static function creationsByDomain(int $days = 30): array
    {
        $byDomain = Cache::remember('stats:creations-domains-30d', (int) config('stats.charts_ttl', 60), function () use ($days) {
            $cutoff = now()->subDays($days - 1)->startOfDay();
            $platformById = Domain::whereNull('user_id')->pluck('hostname', 'id')->all();
            $platformHostnames = array_flip(array_values($platformById));

            $buckets = [];

            $liveRows = Link::query()
                ->selectRaw('domain_id, DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', $cutoff)
                ->groupBy('domain_id')
                ->groupByRaw('DATE(created_at)')
                ->get();

            foreach ($liveRows as $row) {
                $label = $platformById[$row->domain_id] ?? 'Custom domains';
                $date = (string) $row->date;
                $buckets[$label][$date] = ($buckets[$label][$date] ?? 0) + (int) $row->count;
            }

            $deletedRows = LinkTombstone::query()
                ->selectRaw('domain_hostname, DATE(created_day) as date, COUNT(*) as count')
                ->where('created_day', '>=', $cutoff->format('Y-m-d'))
                ->groupBy('domain_hostname')
                ->groupByRaw('DATE(created_day)')
                ->get();

            foreach ($deletedRows as $row) {
                $host = (string) $row->domain_hostname;
                $label = isset($platformHostnames[$host]) ? $host : 'Custom domains';
                $date = (string) $row->date;
                $buckets[$label][$date] = ($buckets[$label][$date] ?? 0) + (int) $row->count;
            }

            return $buckets;
        });

        return self::formatDomainDatasets($byDomain, $days, 'link', null, 'href.nz');
    }

    /**
     * Clicks per day, zero-filled.
     *
     * @return array{labels: list<string>, values: list<int>}
     */
    public static function clicksByDay(int $days = 30): array
    {
        $counts = Cache::remember('stats:clicks-30d', (int) config('stats.charts_ttl', 60), function () {
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
     * Clicks per day broken down by platform domain (+ aggregate
     * "Custom domains" bucket), merging tombstoned click histories.
     *
     * @return array{labels: list<string>, values: list<int>, datasets: list<array<string, mixed>>}
     */
    public static function clicksByDomain(int $days = 30): array
    {
        $byDomain = Cache::remember('stats:clicks-domains-30d', (int) config('stats.charts_ttl', 60), function () use ($days) {
            $cutoff = now()->subDays($days - 1)->startOfDay();
            $cutoffDate = $cutoff->format('Y-m-d');
            $platformById = Domain::whereNull('user_id')->pluck('hostname', 'id')->all();
            $platformHostnames = array_flip(array_values($platformById));

            $buckets = [];

            $liveRows = Click::query()
                ->leftJoin('links', 'clicks.link_id', '=', 'links.id')
                ->selectRaw('links.domain_id as domain_id, DATE(clicks.created_at) as date, COUNT(*) as count')
                ->where('clicks.created_at', '>=', $cutoff)
                ->groupBy('links.domain_id')
                ->groupByRaw('DATE(clicks.created_at)')
                ->get();

            foreach ($liveRows as $row) {
                $label = $row->domain_id !== null
                    ? ($platformById[$row->domain_id] ?? 'Custom domains')
                    : 'href.nz';
                $date = (string) $row->date;
                $buckets[$label][$date] = ($buckets[$label][$date] ?? 0) + (int) $row->count;
            }

            $tombstones = LinkTombstone::query()
                ->whereNotNull('clicks_by_day')
                ->get(['domain_hostname', 'clicks_by_day']);

            foreach ($tombstones as $tomb) {
                $host = (string) $tomb->domain_hostname;
                $label = isset($platformHostnames[$host]) ? $host : 'Custom domains';
                foreach ((array) $tomb->clicks_by_day as $date => $count) {
                    if ($date >= $cutoffDate) {
                        $buckets[$label][$date] = ($buckets[$label][$date] ?? 0) + (int) $count;
                    }
                }
            }

            return $buckets;
        });

        return self::formatDomainDatasets($byDomain, $days, 'click', null, 'href.nz');
    }

    /**
     * QR codes generated per day, zero-filled.
     *
     * @return array{labels: list<string>, values: list<int>}
     */
    public static function qrByDay(int $days = 30): array
    {
        $counts = Cache::remember('stats:qr-30d', (int) config('stats.charts_ttl', 60), function () {
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
     * QR codes generated per day broken down by link domain (plus
     * "qr.href.nz / Direct QR" for standalone QR Studio & /v1/qr renders).
     *
     * @return array{labels: list<string>, values: list<int>, datasets: list<array<string, mixed>>}
     */
    public static function qrByDomain(int $days = 30): array
    {
        $byDomain = Cache::remember('stats:qr-domains-30d', (int) config('stats.charts_ttl', 60), function () use ($days) {
            $cutoff = now()->subDays($days - 1)->startOfDay();
            $platformById = Domain::whereNull('user_id')->pluck('hostname', 'id')->all();

            $buckets = [];

            $rows = QrGeneration::query()
                ->leftJoin('links', 'qr_generations.link_id', '=', 'links.id')
                ->selectRaw('links.domain_id as domain_id, DATE(qr_generations.created_at) as date, COUNT(*) as count')
                ->where('qr_generations.created_at', '>=', $cutoff)
                ->groupBy('links.domain_id')
                ->groupByRaw('DATE(qr_generations.created_at)')
                ->get();

            foreach ($rows as $row) {
                $label = $row->domain_id !== null
                    ? ($platformById[$row->domain_id] ?? 'Custom domains')
                    : 'qr.href.nz / Direct QR';
                $date = (string) $row->date;
                $buckets[$label][$date] = ($buckets[$label][$date] ?? 0) + (int) $row->count;
            }

            return $buckets;
        });

        return self::formatDomainDatasets($byDomain, $days, 'QR code', 'generated', 'qr.href.nz / Direct QR');
    }

    /**
     * Normalize per-domain daily maps into sorted Chart.js datasets with distinct colors.
     *
     * @param  array<string, array<string, int>>  $byDomain
     * @return array{labels: list<string>, values: list<int>, datasets: list<array<string, mixed>>}
     */
    private static function formatDomainDatasets(
        array $byDomain,
        int $days,
        string $unit,
        ?string $verb,
        string $emptyFallbackDomain,
    ): array {
        $window = self::lastDays($days);
        $labels = [];
        $dates = [];

        foreach ($window as $day) {
            $labels[] = $day['label'];
            $dates[] = $day['date'];
        }

        $totals = array_fill(0, count($dates), 0);
        $series = [];

        foreach ($byDomain as $domainLabel => $dailyCounts) {
            $vals = [];
            $sum = 0;
            foreach ($dates as $idx => $d) {
                $c = (int) ($dailyCounts[$d] ?? 0);
                $vals[] = $c;
                $sum += $c;
                $totals[$idx] += $c;
            }

            if ($sum > 0) {
                $colors = self::domainColor($domainLabel);
                $dataset = [
                    'label' => $domainLabel,
                    'values' => $vals,
                    'sum' => $sum,
                    'colorLight' => $colors['light'],
                    'colorDark' => $colors['dark'],
                    'unit' => $unit,
                ];
                if ($verb !== null) {
                    $dataset['verb'] = $verb;
                }
                $series[] = $dataset;
            }
        }

        usort($series, fn (array $a, array $b) => $b['sum'] <=> $a['sum']);

        if ($series === []) {
            $colors = self::domainColor($emptyFallbackDomain);
            $fallback = [
                'label' => $emptyFallbackDomain,
                'values' => array_fill(0, count($dates), 0),
                'colorLight' => $colors['light'],
                'colorDark' => $colors['dark'],
                'unit' => $unit,
            ];
            if ($verb !== null) {
                $fallback['verb'] = $verb;
            }
            $series[] = $fallback;
        } else {
            $series = array_map(fn (array $ds) => Arr::except($ds, ['sum']), $series);
        }

        return [
            'labels' => $labels,
            'values' => $totals,
            'datasets' => array_values($series),
        ];
    }

    /**
     * Aggregate network heatmaps (cached 10 minutes):
     * - `hourly`: 7×24 (Mon..Sun × 00..23 UTC) grids over the last 90 days
     *   for clicks, link creations, and QR generations.
     * - `calendar`: 52-week (364-day) daily calendars [Y-m-d => count]
     *   merging live records and tombstones for clicks, links, and QR codes.
     *
     * @return array{
     *     hourly: array{clicks: array<int, array<int, int>>, links: array<int, array<int, int>>, qr: array<int, array<int, int>>},
     *     calendar: array{clicks: array<string, int>, links: array<string, int>, qr: array<string, int>}
     * }
     */
    public static function heatmaps(int $calendarDays = 364, int $hourlyDays = 90): array
    {
        return Cache::remember("stats:heatmaps:{$calendarDays}:{$hourlyDays}", (int) config('stats.heatmaps_ttl', 600), function () use ($calendarDays, $hourlyDays) {
            $insights = app(LinkInsights::class);
            $hourlyCutoff = now()->subDays($hourlyDays - 1)->startOfDay();
            $calendarCutoff = now()->subDays($calendarDays - 1)->startOfDay();

            $hourlyClicks = $insights->weekdayHourGrid(
                Click::query()->where('created_at', '>=', $hourlyCutoff)
            );
            $hourlyLinks = $insights->weekdayHourGrid(
                Link::query()->where('created_at', '>=', $hourlyCutoff)
            );
            $hourlyQr = $insights->weekdayHourGrid(
                QrGeneration::query()->where('created_at', '>=', $hourlyCutoff)
            );

            // Daily calendar maps (last 364 days / 52 weeks)
            $liveClicks = Click::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', $calendarCutoff)
                ->groupByRaw('DATE(created_at)')
                ->pluck('count', 'date')
                ->map(fn ($c) => (int) $c)
                ->all();

            foreach (self::tombstoneClicksByDay() as $date => $count) {
                $liveClicks[$date] = ($liveClicks[$date] ?? 0) + $count;
            }

            $liveLinks = Link::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', $calendarCutoff)
                ->groupByRaw('DATE(created_at)')
                ->pluck('count', 'date')
                ->map(fn ($c) => (int) $c)
                ->all();

            $deletedLinks = LinkTombstone::selectRaw('DATE(created_day) as date, COUNT(*) as count')
                ->where('created_day', '>=', $calendarCutoff->format('Y-m-d'))
                ->groupByRaw('DATE(created_day)')
                ->pluck('count', 'date')
                ->map(fn ($c) => (int) $c)
                ->all();

            foreach ($deletedLinks as $date => $count) {
                $liveLinks[$date] = ($liveLinks[$date] ?? 0) + $count;
            }

            $liveQr = QrGeneration::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', $calendarCutoff)
                ->groupByRaw('DATE(created_at)')
                ->pluck('count', 'date')
                ->map(fn ($c) => (int) $c)
                ->all();

            $calendarClicks = [];
            $calendarLinks = [];
            $calendarQr = [];

            foreach (self::lastDays($calendarDays) as $day) {
                $d = $day['date'];
                $calendarClicks[$d] = (int) ($liveClicks[$d] ?? 0);
                $calendarLinks[$d] = (int) ($liveLinks[$d] ?? 0);
                $calendarQr[$d] = (int) ($liveQr[$d] ?? 0);
            }

            return [
                'hourly' => [
                    'clicks' => $hourlyClicks,
                    'links' => $hourlyLinks,
                    'qr' => $hourlyQr,
                ],
                'calendar' => [
                    'clicks' => $calendarClicks,
                    'links' => $calendarLinks,
                    'qr' => $calendarQr,
                ],
            ];
        });
    }

    /**
     * Compute aggregated weekday, peak hour, and daypart rhythm summaries for heatmaps.
     *
     * @param  array{hourly?: array<string, array<int, array<int, int>>>, calendar?: array<string, array<string, int>>}|null  $heatmaps
     * @return array<string, array<string, mixed>>
     */
    public static function heatmapSummaries(?array $heatmaps = null): array
    {
        $heatmaps ??= self::heatmaps();
        $weekdayNamesFull = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $daypartBlocks = [
            ['label' => 'Night', 'range' => '00–05', 'offset' => 0],
            ['label' => 'Morning', 'range' => '06–11', 'offset' => 6],
            ['label' => 'Afternoon', 'range' => '12–17', 'offset' => 12],
            ['label' => 'Evening', 'range' => '18–23', 'offset' => 18],
        ];

        $summaries = [];
        foreach (['clicks', 'links', 'qr'] as $mKey) {
            $calData = $heatmaps['calendar'][$mKey] ?? [];
            $hrGrid = $heatmaps['hourly'][$mKey] ?? [];

            $calSum = array_sum($calData);
            $rowSums = array_fill(0, 7, 0);
            $hourSums = array_fill(0, 24, 0);
            $dpCounts = [0, 0, 0, 0];
            $total90 = 0;

            for ($w = 0; $w < 7; $w++) {
                for ($h = 0; $h < 24; $h++) {
                    $v = (int) ($hrGrid[$w][$h] ?? 0);
                    $rowSums[$w] += $v;
                    $hourSums[$h] += $v;
                    $dpCounts[(int) intdiv($h, 6)] += $v;
                    $total90 += $v;
                }
            }

            $bestDayVal = max($rowSums);
            $bestDayIdx = $bestDayVal > 0 ? (int) array_search($bestDayVal, $rowSums, true) : null;

            $bestHrVal = max($hourSums);
            $bestHrIdx = $bestHrVal > 0 ? (int) array_search($bestHrVal, $hourSums, true) : null;

            $workweekSum = array_sum(array_slice($rowSums, 0, 5));
            $weekendSum = $rowSums[5] + $rowSums[6];
            $workweekPct = $total90 > 0 ? (int) round(($workweekSum / $total90) * 100) : 0;
            $weekendPct = $total90 > 0 ? max(0, 100 - $workweekPct) : 0;

            $maxDp = max(1, max($dpCounts));
            $dayparts = [];
            foreach ($daypartBlocks as $dpIdx => $dpMeta) {
                $c = $dpCounts[$dpIdx];
                $dayparts[] = [
                    'label' => $dpMeta['label'],
                    'range' => $dpMeta['range'],
                    'count' => $c,
                    'sharePct' => $total90 > 0 ? (int) round(($c / $total90) * 100) : 0,
                    'barPct' => $c > 0 ? max(8, (int) round(($c / $maxDp) * 100)) : 0,
                ];
            }

            $summaries[$mKey] = [
                'calendarTotal' => $calSum,
                'total90' => $total90,
                'bestDayLabel' => $bestDayIdx !== null ? $weekdayNamesFull[$bestDayIdx] : '—',
                'bestDayCount' => $bestDayVal,
                'bestDayShare' => $total90 > 0 ? (int) round(($bestDayVal / $total90) * 100) : 0,
                'bestHourLabel' => $bestHrIdx !== null ? str_pad((string) $bestHrIdx, 2, '0', STR_PAD_LEFT).':00 UTC' : '—',
                'bestHourCount' => $bestHrVal,
                'bestHourShare' => $total90 > 0 ? (int) round(($bestHrVal / $total90) * 100) : 0,
                'workweekSum' => $workweekSum,
                'weekendSum' => $weekendSum,
                'workweekPct' => $workweekPct,
                'weekendPct' => $weekendPct,
                'dayparts' => $dayparts,
            ];
        }

        return $summaries;
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
            Cache::forget('stats:domains:v2');
            $rows = self::buildDomainRows();
            Cache::put('stats:domains:v2', $rows, (int) config('stats.domains_ttl', 600));
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
        foreach ([
            'stats:overview:v2',
            'stats:creations-30d',
            'stats:creations-domains-30d',
            'stats:clicks-30d',
            'stats:clicks-domains-30d',
            'stats:qr-30d',
            'stats:qr-domains-30d',
            'stats:heatmaps:364:90',
            'stats:domains',
            'stats:domains:v2',
        ] as $key) {
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
