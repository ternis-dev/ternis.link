<?php

namespace App\Services;

use App\Models\Click;
use App\Models\Link;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Insights over the public shortener links: when people click, what
 * they come from, and what is about to expire.
 *
 * The numbers are computed in the database (grouping by weekday/hour),
 * not by loading clicks into PHP — a busy account has millions of rows.
 */
class LinkInsights
{
    /**
     * Click counts per weekday and hour, as a 7×24 grid.
     *
     * Index 0 is Monday, 6 is Sunday; the hour index is the local hour
     * of the click. The grid is sparse: missing cells mean "no clicks".
     *
     * @return array<int, array<int, int>>
     */
    public function clickHeatmap(HasMany|Builder $links, int $days = 90): array
    {
        $rows = $this->clicksQuery($links, $days)
            ->selectRaw(
                'CAST(strftime("%w", created_at) AS INTEGER) as weekday,'.
                'CAST(strftime("%H", created_at) AS INTEGER) as hour,'.
                'COUNT(*) as total'
            )
            ->groupBy('weekday', 'hour')
            ->get();

        $grid = array_fill(0, 7, array_fill(0, 24, 0));

        foreach ($rows as $row) {
            // SQLite's %w is Sunday-based (0 = Sunday); shift it to
            // Monday-based so the grid reads Monday → Sunday.
            $weekday = ((int) $row->weekday + 6) % 7;
            $hour = (int) $row->hour;

            $grid[$weekday][$hour] = (int) $row->total;
        }

        return $grid;
    }

    /**
     * The three busiest hours of the week, as
     * ['label' => 'Mon 14:00', 'count' => 42].
     *
     * @return list<array{label: string, count: int}>
     */
    public function bestTimes(HasMany|Builder $links, int $days = 90): array
    {
        $grid = $this->clickHeatmap($links, $days);
        $days_ = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        $flat = [];

        foreach ($grid as $weekday => $hours) {
            foreach ($hours as $hour => $count) {
                if ($count > 0) {
                    $flat[] = [
                        'label' => $days_[$weekday].' '.str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00',
                        'count' => $count,
                    ];
                }
            }
        }

        usort($flat, fn ($a, $b) => $b['count'] <=> $a['count']);

        return array_slice($flat, 0, 5);
    }

    /**
     * Clicks per hour of the day, averaged over the whole week.
     *
     * @return list<array{hour: int, count: int}>
     */
    public function hourlyProfile(HasMany|Builder $links, int $days = 90): array
    {
        $grid = $this->clickHeatmap($links, $days);
        $profile = [];

        for ($hour = 0; $hour < 24; $hour++) {
            $total = 0;
            for ($weekday = 0; $weekday < 7; $weekday++) {
                $total += $grid[$weekday][$hour];
            }
            $profile[] = ['hour' => $hour, 'count' => $total];
        }

        return $profile;
    }

    /**
     * The links that expire soon, grouped by the day they expire on.
     *
     * @return Collection<int, array{date: string, label: string, links: Collection}>
     */
    public function expiryCalendar(HasMany|Builder $links, int $days = 60): Collection
    {
        return $links->clone()
            ->where('is_active', true)
            ->whereNotNull('links.expires_at')
            ->where('links.expires_at', '>', now())
            ->where('links.expires_at', '<=', now()->addDays($days))
            ->with('domain')
            ->orderBy('links.expires_at')
            ->get()
            ->groupBy(fn (Link $link) => $link->expires_at->toDateString())
            ->map(fn (Collection $group, string $date) => [
                'date' => $date,
                'label' => Carbon::parse($date)->format('M j'),
                'links' => $group,
            ])
            ->values();
    }

    /**
     * Top referrers over the period.
     *
     * @return list<array{referrer: string, count: int, share: float}>
     */
    public function topReferrers(HasMany|Builder $links, int $days = 90, int $limit = 8): array
    {
        $total = (int) $this->clicksQuery($links, $days)->count();

        if ($total === 0) {
            return [];
        }

        return $this->clicksQuery($links, $days)
            ->selectRaw('referrer, COUNT(*) as total')
            ->whereNotNull('referrer')
            ->groupBy('referrer')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'referrer' => (string) $row->referrer,
                'count' => (int) $row->total,
                'share' => round((int) $row->total / $total * 100, 1),
            ])
            ->all();
    }

    /**
     * Top countries over the period.
     *
     * @return list<array{country: string, count: int, share: float}>
     */
    public function topCountries(HasMany|Builder $links, int $days = 90, int $limit = 8): array
    {
        $total = (int) $this->clicksQuery($links, $days)->count();

        if ($total === 0) {
            return [];
        }

        return $this->clicksQuery($links, $days)
            ->selectRaw('country_code, COUNT(*) as total')
            ->whereNotNull('country_code')
            ->groupBy('country_code')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'country' => (string) $row->country_code,
                'count' => (int) $row->total,
                'share' => round((int) $row->total / $total * 100, 1),
            ])
            ->all();
    }

    /**
     * Clicks on the given links, excluding direct-URL hits.
     */
    private function clicksQuery(HasMany|Builder $links, int $days): Builder
    {
        $linkIds = $links->clone()->select('links.id');

        return Click::whereIn('link_id', $linkIds)
            ->where('created_at', '>=', now()->subDays($days)->startOfDay())
            ->where('is_direct_url', false);
    }
}
