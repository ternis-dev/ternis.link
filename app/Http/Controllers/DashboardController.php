<?php

namespace App\Http\Controllers;

use App\Models\Click;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Dashboard home — overview stats.
     *
     * Direct-URL redirect clicks stay admin-only here too, matching
     * the API and Livewire analytics visibility rules.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $clicks = Click::whereIn('link_id', $user->links()->select('links.id'))
            ->when(! $user->isAdmin(), fn ($query) => $query->where('is_direct_url', false));

        $stats = [
            'total_links' => $user->links()->count(),
            'total_clicks' => (clone $clicks)->count(),
            'links_this_month' => $user->links()
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
            'clicks_today' => (clone $clicks)
                ->where('created_at', '>=', now()->startOfDay())
                ->count(),
        ];

        return view('dashboard.index', compact('stats'));
    }

    /**
     * Links management page (Livewire: LinkTable).
     */
    public function links()
    {
        return view('dashboard.links.index');
    }

    /**
     * Create link page (Livewire: LinkForm).
     */
    public function createLink()
    {
        return view('dashboard.links.create');
    }

    /**
     * Link detail + analytics page (Livewire: LinkAnalytics).
     */
    public function showLink(int $linkId)
    {
        $link = auth()->user()->links()->with('domain')->findOrFail($linkId);

        return view('dashboard.links.show', compact('link'));
    }

    /**
     * API keys management page (Livewire: ApiKeyManager).
     */
    public function apiKeys()
    {
        return view('dashboard.api-keys.index');
    }
}
