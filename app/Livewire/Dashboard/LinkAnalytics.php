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
     * Selectable analytics windows (days).
     */
    public const PERIODS = [7, 30, 90];

    public function setPeriod(int $days): void
    {
        $this->period = in_array($days, self::PERIODS, true) ? $days : 30;
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
            'averagePerDay' => $this->period > 0 ? round($totalClicks / $this->period, 1) : 0,
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
