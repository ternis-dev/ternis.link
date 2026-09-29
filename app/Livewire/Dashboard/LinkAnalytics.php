<?php

namespace App\Livewire\Dashboard;

use App\Models\Link;
use Illuminate\Support\Collection;
use Livewire\Component;

class LinkAnalytics extends Component
{
    public Link $link;

    public int $period = 30;

    /**
     * Clicks-over-time rendering: bars (default) or a line graph.
     */
    public string $chartType = 'bar';

    /**
     * Selectable analytics windows (days) — capped by link age, see
     * availablePeriods().
     */
    public const PERIODS = [7, 30, 90];

    public const CHART_TYPES = ['bar', 'line'];

    public function mount(): void
    {
        // A fresh link starts on the longest window its age allows
        // instead of a mostly-empty 30-day view.
        $this->period = min($this->period, $this->maxPeriod());
    }

    public function setPeriod(int $days): void
    {
        $available = $this->availablePeriods();
        $this->period = in_array($days, $available, true) ? $days : max($available);
    }

    public function setChartType(string $type): void
    {
        $type = strtolower($type);
        $this->chartType = in_array($type, self::CHART_TYPES, true) ? $type : 'bar';
    }

    /**
     * Windows the link is old enough for: younger than 7 days → 7d
     * only; younger than 30 days → 7/30d; older → 7/30/90d. Ranges
     * beyond the link's age would render mostly zeros, so they are
     * not offered.
     *
     * @return list<int>
     */
    public function availablePeriods(): array
    {
        $max = $this->maxPeriod();

        return array_values(array_filter(self::PERIODS, fn (int $days) => $days <= $max));
    }

    private function maxPeriod(): int
    {
        $ageDays = $this->link->created_at->diffInDays(now()) + 1;

        return $ageDays <= 7 ? 7 : ($ageDays <= 30 ? 30 : 90);
    }

    public function render()
    {
        // Dash analytics are strictly per-user: direct-URL redirect
        // clicks are excluded for everyone (admins included).
        $baseQuery = $this->link->clicks()
            ->where('created_at', '>=', now()->subDays($this->period)->startOfDay())
            ->where('is_direct_url', false);

        $totalClicks = (clone $baseQuery)->count();
        $uniqueVisitors = (clone $baseQuery)->distinct('ip_hash')->count('ip_hash');

        $topReferrers = (clone $baseQuery)
            ->selectRaw('referrer, COUNT(*) as count')
            ->whereNotNull('referrer')
            ->groupBy('referrer')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $topCountries = (clone $baseQuery)
            ->selectRaw('country_code, COUNT(*) as count')
            ->whereNotNull('country_code')
            ->groupBy('country_code')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $clicksByDay = $this->clicksByDay($baseQuery);

        $recentClicks = (clone $baseQuery)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('livewire.dashboard.link-analytics', [
            'totalClicks' => $totalClicks,
            'uniqueVisitors' => $uniqueVisitors,
            'averagePerDay' => round($totalClicks / $this->averageDivisor(), 1),
            'peakDay' => $clicksByDay->sortByDesc('count')->first(),
            'maxDailyClicks' => max(1, (int) $clicksByDay->max('count')),
            'topReferrers' => $topReferrers,
            'topCountries' => $topCountries,
            'topBrowsers' => $this->topBrowsers($baseQuery),
            'clicksByDay' => $clicksByDay,
            'recentClicks' => $recentClicks,
        ]);
    }

    /**
     * Days to average over: the selected window, capped at the link's
     * age (a 2-day-old link with 4 clicks averages 2/day, not 4/30).
     * Minimum 1 so links created today divide by today, never by zero.
     */
    private function averageDivisor(): int
    {
        $ageDays = $this->link->created_at->diffInDays(now()) + 1;

        return max(1, min($this->period, $ageDays));
    }

    /**
     * Daily click counts for the selected window, zero-filled so the
     * chart always renders a continuous range (oldest → newest).
     *
     * @return Collection<int, array{date: string, label: string, count: int}>
     */
    private function clicksByDay(mixed $baseQuery): Collection
    {
        $counts = (clone $baseQuery)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupByRaw('DATE(created_at)')
            ->pluck('count', 'date')
            ->map(fn ($count) => (int) $count);

        $days = collect();

        for ($i = $this->period - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');

            $days->push([
                'date' => $date,
                'label' => now()->subDays($i)->format('M j'),
                'count' => (int) ($counts[$date] ?? 0),
            ]);
        }

        return $days;
    }

    /**
     * Bucket raw user agents into browser families for the breakdown card.
     *
     * @return Collection<int, array{browser: string, count: int}>
     */
    private function topBrowsers(mixed $baseQuery): Collection
    {
        $raw = (clone $baseQuery)
            ->selectRaw('user_agent, COUNT(*) as count')
            ->groupBy('user_agent')
            ->orderByDesc('count')
            ->limit(50)
            ->get();

        return $raw
            ->groupBy(fn ($row) => self::browserFamily($row->user_agent))
            ->map(fn ($rows, $browser) => [
                'browser' => $browser,
                'count' => (int) $rows->sum('count'),
            ])
            ->sortByDesc('count')
            ->take(6)
            ->values();
    }

    public static function browserFamily(?string $userAgent): string
    {
        if ($userAgent === null || trim($userAgent) === '') {
            return 'Unknown';
        }

        if (preg_match('/bot|crawl|spider|slurp|mediapartners|baidu|yandex|sogou|exabot|facebot|ia_archiver|ahrefs|semrush/i', $userAgent)) {
            return 'Bots';
        }

        if (preg_match('/curl|wget|python|go-http|java|okhttp|httpie/i', $userAgent)) {
            return 'Scripts';
        }

        if (str_contains($userAgent, 'Edg/')) {
            return 'Edge';
        }

        if (preg_match('/OPR\/|Opera/', $userAgent)) {
            return 'Opera';
        }

        if (str_contains($userAgent, 'Chrome/')) {
            return 'Chrome';
        }

        if (str_contains($userAgent, 'Firefox/')) {
            return 'Firefox';
        }

        if (str_contains($userAgent, 'Safari/')) {
            return 'Safari';
        }

        return 'Other';
    }
}
