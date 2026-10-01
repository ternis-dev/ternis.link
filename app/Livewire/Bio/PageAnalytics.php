<?php

namespace App\Livewire\Bio;

use App\Livewire\Dashboard\LinkAnalytics;
use App\Models\BioPage;
use Livewire\Component;

class PageAnalytics extends Component
{
    public BioPage $page;

    public int $period = 30;

    public const PERIODS = [7, 30, 90];

    public function mount(BioPage $page): void
    {
        $this->page = $page;
    }

    public function setPeriod(int $days): void
    {
        $this->period = in_array($days, self::PERIODS, true) ? $days : 30;
    }

    public function render()
    {
        $since = now()->subDays($this->period)->startOfDay();

        $views = $this->page->events()->where('kind', 'view')->where('created_at', '>=', $since)->count();
        $taps = $this->page->events()->where('kind', 'tap')->where('created_at', '>=', $since)->count();
        $uniqueVisitors = $this->page->events()->where('created_at', '>=', $since)->distinct('ip_hash')->count('ip_hash');

        $byButton = $this->page->buttons()->orderBy('sort_order')->get()->map(function ($b) use ($since, $taps, $views) {
            $count = $this->page->events()->where('kind', 'tap')->where('bio_button_id', $b->id)->where('created_at', '>=', $since)->count();

            return ['label' => $b->label, 'taps' => $count, 'share' => $taps > 0 ? round($count / $taps * 100, 1) : 0, 'ctr' => $views > 0 ? round($count / $views * 100, 1) : null];
        });

        $counts = $this->page->events()
            ->selectRaw('DATE(created_at) as date, kind, COUNT(*) as count')
            ->where('created_at', '>=', $since)
            ->groupByRaw('DATE(created_at), kind')
            ->get();

        $byDay = collect();
        for ($i = $this->period - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $byDay->push([
                'date' => $date,
                'label' => now()->subDays($i)->format('M j'),
                'views' => (int) ($counts->firstWhere(fn ($r) => $r->date === $date && $r->kind === 'view')->count ?? 0),
                'taps' => (int) ($counts->firstWhere(fn ($r) => $r->date === $date && $r->kind === 'tap')->count ?? 0),
            ]);
        }

        $baseEvents = $this->page->events()->where('created_at', '>=', $since);

        $topReferrers = (clone $baseEvents)
            ->selectRaw('referrer, COUNT(*) as count')
            ->whereNotNull('referrer')
            ->groupBy('referrer')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $topCountries = (clone $baseEvents)
            ->selectRaw('country_code, COUNT(*) as count')
            ->whereNotNull('country_code')
            ->groupBy('country_code')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $bySubpage = collect();
        if ($this->page->parent_id === null) {
            foreach ($this->page->children()->where('is_removed', false)->orderBy('sort_order')->get() as $sub) {
                $bySubpage->push([
                    'id' => $sub->id,
                    'slug' => $sub->slug,
                    'title' => $sub->title,
                    'views' => $sub->events()->where('kind', 'view')->where('created_at', '>=', $since)->count(),
                    'taps' => $sub->events()->where('kind', 'tap')->where('created_at', '>=', $since)->count(),
                ]);
            }
        }

        $browsers = $this->page->events()
            ->selectRaw('user_agent, COUNT(*) as count')
            ->where('created_at', '>=', $since)
            ->groupBy('user_agent')
            ->orderByDesc('count')
            ->limit(50)
            ->get()
            ->groupBy(fn ($row) => LinkAnalytics::browserFamily($row->user_agent))
            ->map(fn ($rows, $browser) => ['browser' => $browser, 'count' => (int) $rows->sum('count')])
            ->sortByDesc('count')
            ->take(6)
            ->values();

        $recent = $this->page->events()
            ->with('button:id,label')
            ->where('created_at', '>=', $since)
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        return view('livewire.bio.page-analytics', [
            'views' => $views,
            'taps' => $taps,
            'uniqueVisitors' => $uniqueVisitors,
            'ctr' => $views > 0 ? round($taps / $views * 100, 1) : null,
            'byButton' => $byButton,
            'byDay' => $byDay,
            'maxDaily' => max(1, (int) $byDay->max(fn ($d) => max($d['views'], $d['taps']))),
            'topReferrers' => $topReferrers,
            'topCountries' => $topCountries,
            'bySubpage' => $bySubpage,
            'topBrowsers' => $browsers,
            'recentEvents' => $recent,
        ]);
    }
}
