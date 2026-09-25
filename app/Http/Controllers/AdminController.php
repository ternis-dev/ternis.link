<?php

namespace App\Http\Controllers;

use App\Models\Click;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * System overview — totals, today, top links.
     */
    public function index(Request $request)
    {
        $stats = [
            'total_users' => User::count(),
            'total_links' => Link::count(),
            'active_links' => Link::where('is_active', true)->count(),
            'total_clicks' => Click::count(),
            'total_domains' => Domain::count(),
            'links_today' => Link::where('created_at', '>=', now()->startOfDay())->count(),
            'clicks_today' => Click::where('created_at', '>=', now()->startOfDay())->count(),
            'direct_url_clicks' => Click::where('is_direct_url', true)->count(),
        ];

        $topLinks = Link::with(['domain', 'user'])
            ->orderByDesc('click_count')
            ->limit(5)
            ->get();

        $recentLinks = Link::with(['domain', 'user'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('admin.index', compact('stats', 'topLinks', 'recentLinks'));
    }

    /**
     * Link moderation page (Livewire: Admin\LinkModeration).
     */
    public function links()
    {
        return view('admin.links');
    }

    /**
     * User management page (Livewire: Admin\UserTable).
     */
    public function users()
    {
        return view('admin.users');
    }

    /**
     * Domain moderation page (Livewire: Admin\DomainModeration).
     */
    public function domains()
    {
        return view('admin.domains');
    }

    /**
     * Audit log page (Livewire: Admin\ActivityLogTable).
     */
    public function activity()
    {
        return view('admin.activity');
    }
}
