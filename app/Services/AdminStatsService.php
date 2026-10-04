<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Click;
use App\Models\Domain;
use App\Models\ErrorEncounter;
use App\Models\Link;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminStatsService
{
    /**
     * Resolve the date cutoff based on `days` query parameter.
     */
    public function resolveDateCutoff(string|int|null $days): ?Carbon
    {
        if ($days === 'all' || $days === null || $days === '') {
            if ($days === 'all') {
                return null;
            }

            // default to 30 days
            return now()->subDays(30)->startOfDay();
        }

        $daysInt = max(1, min(365, (int) $days));

        return now()->subDays($daysInt)->startOfDay();
    }

    /**
     * Generate an array of date strings between start and today with 0 as initial count.
     *
     * @return array<string, int>
     */
    public function generateDateRange(?Carbon $startDate): array
    {
        $start = $startDate ? $startDate->copy()->startOfDay() : now()->subDays(30)->startOfDay();
        $end = now()->startOfDay();

        $period = CarbonPeriod::create($start, '1 day', $end);
        $timeline = [];

        foreach ($period as $date) {
            $timeline[$date->format('Y-m-d')] = 0;
        }

        return $timeline;
    }

    /**
     * Merge DB grouped counts into the date range.
     *
     * @param  array<string, int>  $range
     * @param  Collection<string, int>  $counts
     * @return array<string, int>
     */
    public function mergeTimeline(array $range, $counts): array
    {
        foreach ($counts as $date => $count) {
            if (array_key_exists((string) $date, $range)) {
                $range[(string) $date] = (int) $count;
            }
        }

        return $range;
    }

    /**
     * System-wide overview statistics.
     */
    public function getOverviewStats(array $params = []): array
    {
        $days = $params['days'] ?? 30;
        $cutoff = $this->resolveDateCutoff($days);

        $totalLinks = Link::count();
        $activeLinks = Link::where('is_active', true)->where('is_removed', false)->count();
        $totalClicks = Click::count();

        $linksInWindow = Link::when($cutoff, fn ($q) => $q->where('created_at', '>=', $cutoff))->count();
        $clicksInWindow = Click::when($cutoff, fn ($q) => $q->where('created_at', '>=', $cutoff))->count();
        $errorsInWindow = ErrorEncounter::when($cutoff, fn ($q) => $q->where('created_at', '>=', $cutoff))->count();
        $activitiesInWindow = ActivityLog::when($cutoff, fn ($q) => $q->where('created_at', '>=', $cutoff))->count();

        // Overall link to click ratio
        $overallRatio = $totalLinks > 0 ? round($totalClicks / $totalLinks, 2) : 0;
        $windowRatio = $linksInWindow > 0 ? round($clicksInWindow / $linksInWindow, 2) : 0;

        // Daily clicks timeline
        $dateRange = $this->generateDateRange($cutoff);
        $dailyClicksQuery = Click::when($cutoff, fn ($q) => $q->where('created_at', '>=', $cutoff))
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');
        $clicksTimeline = $this->mergeTimeline($dateRange, $dailyClicksQuery);

        // Daily links timeline
        $dailyLinksQuery = Link::when($cutoff, fn ($q) => $q->where('created_at', '>=', $cutoff))
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');
        $linksTimeline = $this->mergeTimeline($dateRange, $dailyLinksQuery);

        // Domains breakdown
        $domainStats = Domain::withCount(['links', 'clicks'])
            ->orderByDesc('clicks_count')
            ->limit(8)
            ->get();

        return [
            'days' => $days,
            'total_links' => $totalLinks,
            'active_links' => $activeLinks,
            'total_clicks' => $totalClicks,
            'links_in_window' => $linksInWindow,
            'clicks_in_window' => $clicksInWindow,
            'errors_in_window' => $errorsInWindow,
            'activities_in_window' => $activitiesInWindow,
            'overall_ratio' => $overallRatio,
            'window_ratio' => $windowRatio,
            'clicks_timeline' => $clicksTimeline,
            'links_timeline' => $linksTimeline,
            'domain_stats' => $domainStats,
            'total_users' => User::count(),
            'total_domains' => Domain::count(),
        ];
    }

    /**
     * Errors statistics with code, path, method, and day breakdowns.
     */
    public function getErrorStats(array $params = []): array
    {
        $days = $params['days'] ?? 30;
        $cutoff = $this->resolveDateCutoff($days);

        $query = ErrorEncounter::query();

        if ($cutoff) {
            $query->where('created_at', '>=', $cutoff);
        }

        if (! empty($params['code'])) {
            $query->where('http_code', (int) $params['code']);
        }

        if (! empty($params['method'])) {
            $query->where('method', strtoupper((string) $params['method']));
        }

        if (! empty($params['path'])) {
            $query->where('path', 'like', '%'.$params['path'].'%');
        }

        if (! empty($params['host'])) {
            $query->where('host', 'like', '%'.$params['host'].'%');
        }

        $totalFiltered = (clone $query)->count();
        $errorsToday = ErrorEncounter::where('created_at', '>=', now()->startOfDay())->count();
        $count4xx = (clone $query)->whereBetween('http_code', [400, 499])->count();
        $count5xx = (clone $query)->whereBetween('http_code', [500, 599])->count();

        // Daily timeline
        $dateRange = $this->generateDateRange($cutoff);
        $dailyQuery = (clone $query)->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');
        $timeline = $this->mergeTimeline($dateRange, $dailyQuery);

        // Group by HTTP code
        $byCode = (clone $query)->selectRaw('http_code, count(*) as count')
            ->groupBy('http_code')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => [
                'code' => (int) $r->http_code,
                'count' => (int) $r->count,
                'percentage' => $totalFiltered > 0 ? round(($r->count / $totalFiltered) * 100, 1) : 0,
            ]);

        // Group by Host
        $byHost = (clone $query)->selectRaw('host, count(*) as count')
            ->groupBy('host')
            ->orderByDesc('count')
            ->limit(6)
            ->get();

        // Top failing paths
        $topPaths = (clone $query)->selectRaw('path, method, http_code, count(*) as count')
            ->groupBy('path', 'method', 'http_code')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Top exception classes
        $topExceptions = (clone $query)->selectRaw('exception_class, count(*) as count')
            ->whereNotNull('exception_class')
            ->groupBy('exception_class')
            ->orderByDesc('count')
            ->limit(8)
            ->get()
            ->map(fn ($r) => [
                'class' => class_basename($r->exception_class),
                'full_class' => $r->exception_class,
                'count' => $r->count,
            ]);

        // Recent encounters
        $recent = (clone $query)->with('user:id,email,name')
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        return [
            'days' => $days,
            'filters' => $params,
            'total_filtered' => $totalFiltered,
            'errors_today' => $errorsToday,
            'count_4xx' => $count4xx,
            'count_5xx' => $count5xx,
            'timeline' => $timeline,
            'by_code' => $byCode,
            'by_host' => $byHost,
            'top_paths' => $topPaths,
            'top_exceptions' => $topExceptions,
            'recent' => $recent,
        ];
    }

    /**
     * Audit activities statistics.
     */
    public function getActivityStats(array $params = []): array
    {
        $days = $params['days'] ?? 30;
        $cutoff = $this->resolveDateCutoff($days);

        $query = ActivityLog::query();

        if ($cutoff) {
            $query->where('created_at', '>=', $cutoff);
        }

        if (! empty($params['action'])) {
            $query->where('action', 'like', '%'.$params['action'].'%');
        }

        $actorId = $params['actor_id'] ?? $params['user_id'] ?? null;
        if (! empty($actorId)) {
            $query->where('actor_id', $actorId);
        }

        if (! empty($params['search'])) {
            $search = '%'.$params['search'].'%';
            $query->where(function (Builder $q) use ($search) {
                $q->where('action', 'like', $search)
                    ->orWhere('ip_hash', 'like', $search);
            });
        }

        $totalFiltered = (clone $query)->count();
        $activitiesToday = ActivityLog::where('created_at', '>=', now()->startOfDay())->count();
        $uniqueActors = (clone $query)->whereNotNull('actor_id')->distinct('actor_id')->count('actor_id');

        // Daily timeline
        $dateRange = $this->generateDateRange($cutoff);
        $dailyQuery = (clone $query)->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');
        $timeline = $this->mergeTimeline($dateRange, $dailyQuery);

        // Group by Action
        $byAction = (clone $query)->selectRaw('action, count(*) as count')
            ->groupBy('action')
            ->orderByDesc('count')
            ->limit(12)
            ->get()
            ->map(fn ($r) => [
                'action' => $r->action,
                'count' => (int) $r->count,
                'percentage' => $totalFiltered > 0 ? round(($r->count / $totalFiltered) * 100, 1) : 0,
            ]);

        // Category breakdown (links, domains, auth, api, admin)
        $categories = [
            'Links' => (clone $query)->where('action', 'like', 'link.%')->count(),
            'Domains' => (clone $query)->where('action', 'like', 'domain.%')->count(),
            'Auth' => (clone $query)->where('action', 'like', 'auth.%')->count(),
            'API Keys' => (clone $query)->where('action', 'like', 'api_key.%')->count(),
            'Admin' => (clone $query)->where('action', 'like', 'admin.%')->count(),
        ];

        // Top users
        $topUsers = (clone $query)->whereNotNull('actor_id')
            ->selectRaw('actor_id, count(*) as count')
            ->groupBy('actor_id')
            ->orderByDesc('count')
            ->limit(8)
            ->with('actor:id,name,email')
            ->get();

        // Recent activity entries
        $recent = (clone $query)->with('actor:id,name,email')
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        return [
            'days' => $days,
            'filters' => $params,
            'total_filtered' => $totalFiltered,
            'activities_today' => $activitiesToday,
            'unique_actors' => $uniqueActors,
            'timeline' => $timeline,
            'by_action' => $byAction,
            'categories' => $categories,
            'top_users' => $topUsers,
            'recent' => $recent,
        ];
    }

    /**
     * Links creation and inventory statistics.
     */
    public function getLinkStats(array $params = []): array
    {
        $days = $params['days'] ?? 30;
        $cutoff = $this->resolveDateCutoff($days);

        $query = Link::query();

        if ($cutoff) {
            $query->where('created_at', '>=', $cutoff);
        }

        if (! empty($params['domain_id'])) {
            $query->where('domain_id', $params['domain_id']);
        }

        if (! empty($params['status'])) {
            if ($params['status'] === 'active') {
                $query->where('is_active', true)->where('is_removed', false);
            } elseif ($params['status'] === 'inactive') {
                $query->where('is_active', false)->where('is_removed', false);
            } elseif ($params['status'] === 'removed') {
                $query->where('is_removed', true);
            } elseif ($params['status'] === 'expired') {
                $query->whereNotNull('expires_at')->where('expires_at', '<=', now());
            }
        }

        if (isset($params['tracking']) && $params['tracking'] !== '') {
            $query->where('user_tracking_enabled', (bool) $params['tracking']);
        }

        if (isset($params['password']) && $params['password'] !== '') {
            if ($params['password'] == '1') {
                $query->whereNotNull('password_hash');
            } else {
                $query->whereNull('password_hash');
            }
        }

        $totalFiltered = (clone $query)->count();
        $linksToday = Link::where('created_at', '>=', now()->startOfDay())->count();
        $activeCount = (clone $query)->where('is_active', true)->where('is_removed', false)->count();
        $passwordCount = (clone $query)->whereNotNull('password_hash')->count();
        $trackingCount = (clone $query)->where('user_tracking_enabled', true)->count();
        $withTagsCount = (clone $query)->whereNotNull('tags')->count();

        // Daily creation timeline
        $dateRange = $this->generateDateRange($cutoff);
        $dailyQuery = (clone $query)->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');
        $timeline = $this->mergeTimeline($dateRange, $dailyQuery);

        // Group by Domain
        $byDomain = (clone $query)->selectRaw('domain_id, count(*) as count')
            ->groupBy('domain_id')
            ->orderByDesc('count')
            ->limit(10)
            ->with('domain:id,hostname')
            ->get();

        // Top created links
        $topLinks = (clone $query)->with(['domain', 'user:id,name,email'])
            ->orderByDesc('click_count')
            ->limit(10)
            ->get();

        $domains = Domain::orderBy('hostname')->get(['id', 'hostname']);

        return [
            'days' => $days,
            'filters' => $params,
            'total_filtered' => $totalFiltered,
            'links_today' => $linksToday,
            'active_count' => $activeCount,
            'password_count' => $passwordCount,
            'tracking_count' => $trackingCount,
            'with_tags_count' => $withTagsCount,
            'timeline' => $timeline,
            'by_domain' => $byDomain,
            'top_links' => $topLinks,
            'domains' => $domains,
        ];
    }

    /**
     * Link clicks traffic statistics.
     */
    public function getClickStats(array $params = []): array
    {
        $days = $params['days'] ?? 30;
        $cutoff = $this->resolveDateCutoff($days);

        $query = Click::query();

        if ($cutoff) {
            $query->where('created_at', '>=', $cutoff);
        }

        if (! empty($params['device'])) {
            $dev = strtolower((string) $params['device']);
            if ($dev === 'mobile') {
                $query->where(fn ($q) => $q->where('user_agent', 'like', '%mobile%')->orWhere('user_agent', 'like', '%iphone%')->orWhere('user_agent', 'like', '%android%'));
            } elseif ($dev === 'tablet') {
                $query->where(fn ($q) => $q->where('user_agent', 'like', '%tablet%')->orWhere('user_agent', 'like', '%ipad%'));
            } elseif ($dev === 'desktop') {
                $query->where(fn ($q) => $q->where('user_agent', 'not like', '%mobile%')->where('user_agent', 'not like', '%iphone%')->where('user_agent', 'not like', '%tablet%')->where('user_agent', 'not like', '%ipad%'));
            }
        }

        if (! empty($params['domain_id'])) {
            $query->whereHas('link', fn ($q) => $q->where('domain_id', $params['domain_id']));
        }

        if (! empty($params['country'])) {
            $query->where('country_code', strtoupper((string) $params['country']));
        }

        if (! empty($params['has_params'])) {
            if ($params['has_params'] == '1') {
                $query->whereNotNull('query_params');
            } else {
                $query->whereNull('query_params');
            }
        }

        if (! empty($params['has_tags'])) {
            if ($params['has_tags'] == '1') {
                $query->whereNotNull('tags');
            } else {
                $query->whereNull('tags');
            }
        }

        $totalFiltered = (clone $query)->count();
        $clicksToday = Click::where('created_at', '>=', now()->startOfDay())->count();
        $directClicks = (clone $query)->where('is_direct_url', true)->count();
        $redirectClicks = (clone $query)->where('is_direct_url', false)->count();
        $withParams = (clone $query)->whereNotNull('query_params')->count();
        $withTags = (clone $query)->whereNotNull('tags')->count();
        $uniqueUsers = (clone $query)->whereNotNull('user_identifier')->distinct('user_identifier')->count('user_identifier');

        // Daily timeline
        $dateRange = $this->generateDateRange($cutoff);
        $dailyQuery = (clone $query)->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');
        $timeline = $this->mergeTimeline($dateRange, $dailyQuery);

        // Device breakdown via user agent inspection
        $mobileCount = (clone $query)->where(fn ($q) => $q->where('user_agent', 'like', '%mobile%')->orWhere('user_agent', 'like', '%iphone%')->orWhere('user_agent', 'like', '%android%'))->count();
        $tabletCount = (clone $query)->where(fn ($q) => $q->where('user_agent', 'like', '%tablet%')->orWhere('user_agent', 'like', '%ipad%'))->count();
        $desktopCount = max(0, $totalFiltered - $mobileCount - $tabletCount);

        $byDevice = [
            ['device' => 'desktop', 'count' => $desktopCount, 'percentage' => $totalFiltered > 0 ? round(($desktopCount / $totalFiltered) * 100, 1) : 0],
            ['device' => 'mobile', 'count' => $mobileCount, 'percentage' => $totalFiltered > 0 ? round(($mobileCount / $totalFiltered) * 100, 1) : 0],
            ['device' => 'tablet', 'count' => $tabletCount, 'percentage' => $totalFiltered > 0 ? round(($tabletCount / $totalFiltered) * 100, 1) : 0],
        ];

        // Group by Referer Host
        $byReferer = (clone $query)->selectRaw('referrer, count(*) as count')
            ->whereNotNull('referrer')
            ->groupBy('referrer')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(function ($r) use ($totalFiltered) {
                $host = parse_url($r->referrer, PHP_URL_HOST) ?: $r->referrer;

                return [
                    'referer' => $host ?: 'Direct / Bookmarks',
                    'count' => (int) $r->count,
                    'percentage' => $totalFiltered > 0 ? round(($r->count / $totalFiltered) * 100, 1) : 0,
                ];
            });

        // Group by Country
        $byCountry = (clone $query)->selectRaw('country_code, count(*) as count')
            ->groupBy('country_code')
            ->orderByDesc('count')
            ->limit(8)
            ->get()
            ->map(fn ($r) => [
                'country' => $r->country_code ?: 'Unknown',
                'count' => (int) $r->count,
                'percentage' => $totalFiltered > 0 ? round(($r->count / $totalFiltered) * 100, 1) : 0,
            ]);

        $domains = Domain::orderBy('hostname')->get(['id', 'hostname']);

        return [
            'days' => $days,
            'filters' => $params,
            'total_filtered' => $totalFiltered,
            'clicks_today' => $clicksToday,
            'direct_clicks' => $directClicks,
            'redirect_clicks' => $redirectClicks,
            'with_params' => $withParams,
            'with_tags' => $withTags,
            'unique_users' => $uniqueUsers,
            'timeline' => $timeline,
            'by_device' => $byDevice,
            'by_referer' => $byReferer,
            'by_country' => $byCountry,
            'domains' => $domains,
        ];
    }

    /**
     * Link/Clicks ratio analysis and tier distribution.
     */
    public function getRatioStats(array $params = []): array
    {
        $days = $params['days'] ?? 30;
        $cutoff = $this->resolveDateCutoff($days);

        $linksQuery = Link::query();
        $clicksQuery = Click::query();

        if ($cutoff) {
            $linksQuery->where('created_at', '>=', $cutoff);
            $clicksQuery->where('created_at', '>=', $cutoff);
        }

        if (! empty($params['domain_id'])) {
            $linksQuery->where('domain_id', $params['domain_id']);
            $clicksQuery->where('domain_id', $params['domain_id']);
        }

        $totalLinks = (clone $linksQuery)->count();
        $totalClicks = (clone $clicksQuery)->count();
        $ratio = $totalLinks > 0 ? round($totalClicks / $totalLinks, 2) : 0;

        // Links with at least 1 click
        $activeClickedLinks = (clone $linksQuery)->where('click_count', '>', 0)->count();
        $dormantLinks = $totalLinks - $activeClickedLinks;
        $activeRatioPercent = $totalLinks > 0 ? round(($activeClickedLinks / $totalLinks) * 100, 1) : 0;

        // Volume distribution tiers
        $tiers = [
            '0 clicks (dormant)' => (clone $linksQuery)->where('click_count', 0)->count(),
            '1 – 5 clicks' => (clone $linksQuery)->whereBetween('click_count', [1, 5])->count(),
            '6 – 25 clicks' => (clone $linksQuery)->whereBetween('click_count', [6, 25])->count(),
            '26 – 100 clicks' => (clone $linksQuery)->whereBetween('click_count', [26, 100])->count(),
            '101 – 1,000 clicks' => (clone $linksQuery)->whereBetween('click_count', [101, 1000])->count(),
            '1,000+ clicks' => (clone $linksQuery)->where('click_count', '>', 1000)->count(),
        ];

        // Ratios per Domain
        $domainRatios = Domain::withCount(['links', 'clicks'])
            ->get()
            ->map(function ($d) {
                return [
                    'id' => $d->id,
                    'hostname' => $d->hostname,
                    'links_count' => $d->links_count,
                    'clicks_count' => $d->clicks_count,
                    'ratio' => $d->links_count > 0 ? round($d->clicks_count / $d->links_count, 2) : 0,
                ];
            })
            ->sortByDesc('ratio')
            ->values();

        // Top ratio links (highest click count)
        $topLinks = (clone $linksQuery)->with(['domain', 'user:id,name,email'])
            ->orderByDesc('click_count')
            ->limit(10)
            ->get();

        $domains = Domain::orderBy('hostname')->get(['id', 'hostname']);

        return [
            'days' => $days,
            'filters' => $params,
            'total_links' => $totalLinks,
            'total_clicks' => $totalClicks,
            'ratio' => $ratio,
            'active_clicked_links' => $activeClickedLinks,
            'dormant_links' => $dormantLinks,
            'active_ratio_percent' => $activeRatioPercent,
            'tiers' => $tiers,
            'domain_ratios' => $domainRatios,
            'top_links' => $topLinks,
            'domains' => $domains,
        ];
    }
}
