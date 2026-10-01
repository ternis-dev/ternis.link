<?php

namespace App\Livewire\Bio;

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

        $byButton = $this->page->buttons()->orderBy('sort_order')->get()->map(function ($b) use ($since, $taps) {
            $count = $this->page->events()->where('kind', 'tap')->where('bio_button_id', $b->id)->where('created_at', '>=', $since)->count();

            return ['label' => $b->label, 'taps' => $count, 'share' => $taps > 0 ? round($count / $taps * 100, 1) : 0];
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

        return view('livewire.bio.page-analytics', [
            'views' => $views,
            'taps' => $taps,
            'ctr' => $views > 0 ? round($taps / $views * 100, 1) : null,
            'byButton' => $byButton,
            'byDay' => $byDay,
            'maxDaily' => max(1, (int) $byDay->max(fn ($d) => max($d['views'], $d['taps']))),
        ]);
    }
}
