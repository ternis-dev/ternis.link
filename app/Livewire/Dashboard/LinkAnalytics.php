<?php

namespace App\Livewire\Dashboard;

use App\Models\Link;
use Livewire\Component;

class LinkAnalytics extends Component
{
    public Link $link;

    public function render()
    {
        $baseQuery = $this->link->clicks();
        if (! auth()->user()->isAdmin()) {
            $baseQuery->where('is_direct_url', false);
        }

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

        $clicksByDay = (clone $baseQuery)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->limit(30)
            ->get();

        $recentClicks = (clone $baseQuery)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('livewire.dashboard.link-analytics', [
            'totalClicks' => $totalClicks,
            'uniqueVisitors' => $uniqueVisitors,
            'topReferrers' => $topReferrers,
            'topCountries' => $topCountries,
            'clicksByDay' => $clicksByDay,
            'recentClicks' => $recentClicks,
        ]);
    }
}
