<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ApiKey;
use App\Models\Click;
use App\Models\Domain;
use App\Models\ErrorEncounter;
use App\Models\Link;
use App\Models\QrGeneration;
use App\Models\User;
use App\Services\AdminStatsService;
use App\Services\LinkInsights;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * System overview — totals, today, 30d charts & heatmaps, top links.
     */
    public function index(Request $request, AdminStatsService $statsService)
    {
        $stats = [
            'total_users' => User::count(),
            'total_links' => Link::count(),
            'active_links' => Link::where('is_active', true)->where('is_removed', false)->count(),
            'removed_links' => Link::where('is_removed', true)->count(),
            'total_clicks' => Click::count(),
            'clicks_today' => Click::where('created_at', '>=', now()->startOfDay())->count(),
            'links_today' => Link::where('created_at', '>=', now()->startOfDay())->count(),
            'direct_url_clicks' => Click::count() > 0 ? Click::where('is_direct_url', true)->count() : 0,
            'total_domains' => Domain::count(),
            'active_api_keys' => ApiKey::whereNull('revoked_at')->count(),
            'qr_codes' => QrGeneration::count(),
            'errors_today' => ErrorEncounter::where('created_at', '>=', now()->startOfDay())->count(),
        ];

        $telemetry = $statsService->getOverviewStats(['days' => 30]);

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

        return view('admin.index', compact('stats', 'telemetry', 'topLinks', 'recentLinks'));
    }

    /**
     * System-wide link insights and telemetry heatmap.
     */
    public function insights(Request $request)
    {
        return redirect()->route('admin.stats.show', array_merge(['section' => 'insights'], $request->query()));
    }

    /**
     * Link moderation page (Livewire: Admin\LinkModeration).
     */
    public function links(LinkInsights $insights)
    {
        $heatmap = $insights->weekdayHourGrid(
            Link::query()->where('created_at', '>=', now()->subDays(30)->startOfDay())
        );

        return view('admin.links', compact('heatmap'));
    }

    /**
     * User management page (Livewire: Admin\UserTable).
     */
    public function users(LinkInsights $insights)
    {
        $heatmap = $insights->weekdayHourGrid(
            User::query()->where('created_at', '>=', now()->subDays(30)->startOfDay())
        );

        return view('admin.users', compact('heatmap'));
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
    public function activity(LinkInsights $insights)
    {
        $heatmap = $insights->weekdayHourGrid(
            ActivityLog::query()->where('created_at', '>=', now()->subDays(30)->startOfDay())
        );

        return view('admin.activity', compact('heatmap'));
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
    public function errors(LinkInsights $insights)
    {
        $heatmap = $insights->weekdayHourGrid(
            ErrorEncounter::query()->where('created_at', '>=', now()->subDays(30)->startOfDay())
        );

        return view('admin.errors', compact('heatmap'));
    }

    /**
     * Allowed pairs and rate limit allowlist (Livewire: Admin\AllowedClientModeration).
     */
    public function allowedClients()
    {
        return view('admin.allowed-clients');
    }
}
