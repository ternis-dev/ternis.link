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
        return $this->weekdayHourGrid($this->clicksQuery($links, $days));
    }

    /**
     * Build a 7×24 (Monday..Sunday × 00..23) count matrix from any Eloquent builder.
     *
     * @return array<int, array<int, int>>
     */
    public function weekdayHourGrid(Builder $query, string $column = 'created_at'): array
    {
        $driver = $query->getConnection()->getDriverName();

        $rows = (clone $query)
            ->reorder()
            ->selectRaw($this->heatmapSelectExpression($driver, $column))
            ->groupBy('weekday', 'hour')
            ->get();

        $grid = array_fill(0, 7, array_fill(0, 24, 0));

        foreach ($rows as $row) {
            $weekday = $this->normalizeWeekday((int) $row->weekday, $driver);
            $hour = (int) $row->hour;

            if ($weekday >= 0 && $weekday < 7 && $hour >= 0 && $hour < 24) {
                $grid[$weekday][$hour] = (int) $row->total;
            }
        }

        return $grid;
    }

    /**
     * Database-specific SQL select expression for grouping by weekday and hour.
     */
    public function heatmapSelectExpression(string $driver, string $column = 'created_at'): string
    {
        return match ($driver) {
            'sqlite' => "CAST(strftime(\"%w\", {$column}) AS INTEGER) as weekday,".
                        "CAST(strftime(\"%H\", {$column}) AS INTEGER) as hour,".
                        'COUNT(*) as total',
            'pgsql' => "CAST(EXTRACT(DOW FROM {$column}) AS INTEGER) as weekday,".
                       "CAST(EXTRACT(HOUR FROM {$column}) AS INTEGER) as hour,".
                       'COUNT(*) as total',
            default => "WEEKDAY({$column}) as weekday,".
                       "HOUR({$column}) as hour,".
                       'COUNT(*) as total',
        };
    }

    /**
     * Normalize weekday from database driver convention to Monday-based index (0 = Monday, 6 = Sunday).
     */
    public function normalizeWeekday(int $rawWeekday, string $driver): int
    {
        // SQLite %w and PostgreSQL EXTRACT(DOW) are Sunday-based (0 = Sunday). Shift to Monday-based.
        // MySQL / MariaDB WEEKDAY() is already Monday-based (0 = Monday, 6 = Sunday).
        return in_array($driver, ['sqlite', 'pgsql'], true)
            ? ($rawWeekday + 6) % 7
            : $rawWeekday;
    }

    /**
     * Extract the busiest weekday/hour slots from an existing 7×24 grid.
     *
     * @param  array<int, array<int, int>>  $grid
     * @return list<array{label: string, count: int}>
     */
    public static function topSlotsFromGrid(array $grid, int $limit = 5): array
    {
        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $flat = [];

        foreach ($grid as $weekday => $hours) {
            foreach ($hours as $hour => $count) {
                if ($count > 0) {
                    $flat[] = [
                        'label' => ($days[$weekday] ?? 'Day').' '.str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00',
                        'count' => (int) $count,
                    ];
                }
            }
        }

        usort($flat, fn ($a, $b) => $b['count'] <=> $a['count']);

        return array_slice($flat, 0, $limit);
    }

    /**
     * Collapse an existing 7×24 grid into a 24-hour distribution profile.
     *
     * @param  array<int, array<int, int>>  $grid
     * @return list<array{hour: int, count: int}>
     */
    public static function hourlyProfileFromGrid(array $grid): array
    {
        $profile = [];

        for ($hour = 0; $hour < 24; $hour++) {
            $total = 0;
            for ($weekday = 0; $weekday < 7; $weekday++) {
                $total += (int) ($grid[$weekday][$hour] ?? 0);
            }
            $profile[] = ['hour' => $hour, 'count' => $total];
        }

        return $profile;
    }

    /**
     * The busiest hours of the week, as ['label' => 'Mon 14:00', 'count' => 42].
     *
     * @return list<array{label: string, count: int}>
     */
    public function bestTimes(HasMany|Builder $links, int $days = 90): array
    {
        return self::topSlotsFromGrid($this->clickHeatmap($links, $days), 5);
    }

    /**
     * Clicks per hour of the day, summed over the whole week.
     *
     * @return list<array{hour: int, count: int}>
     */
    public function hourlyProfile(HasMany|Builder $links, int $days = 90): array
    {
        return self::hourlyProfileFromGrid($this->clickHeatmap($links, $days));
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
