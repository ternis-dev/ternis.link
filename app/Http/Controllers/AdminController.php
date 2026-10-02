<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Models\Click;
use App\Models\Domain;
use App\Models\ErrorEncounter;
use App\Models\Link;
use App\Models\QrGeneration;
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
            'active_links' => Link::where('is_active', true)->where('is_removed', false)->count(),
            'removed_links' => Link::where('is_removed', true)->count(),
            'total_clicks' => Click::count(),
            'clicks_today' => Click::where('created_at', '>=', now()->startOfDay())->count(),
            'links_today' => Link::where('created_at', '>=', now()->startOfDay())->count(),
            'direct_url_clicks' => Click::where('is_direct_url', true)->count(),
            'total_domains' => Domain::count(),
            'active_api_keys' => ApiKey::whereNull('revoked_at')->count(),
            'qr_codes' => QrGeneration::count(),
            'errors_today' => ErrorEncounter::where('created_at', '>=', now()->startOfDay())->count(),
        ];

        $topLinks = Link::notRemoved()
            ->with(['domain', 'user'])
            ->orderByDesc('click_count')
            ->limit(5)
            ->get();

        $recentLinks = Link::notRemoved()
            ->with(['domain', 'user'])
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

    /**
     * Bio page moderation (Livewire: Admin\BioModeration).
     */
    public function bio()
    {
        return view('admin.bio');
    }

    /**
     * Error encounters page (Livewire: Admin\ErrorEncounterTable).
     */
    public function errors()
    {
        return view('admin.errors');
    }
}
