<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Click;
use App\Models\Link;
use App\Support\LinkQrCode;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Dashboard home — personal overview stats for the signed-in user.
     *
     * System-wide stats live on the admin host (AdminController); this
     * endpoint is strictly per-user, including for admins.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $clicks = Click::whereIn('link_id', $user->links()->select('links.id'))
            ->where('is_direct_url', false);

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
     *
     * Strictly per-user: admins manage other users' links from the
     * admin host (Link Moderation), not from dash.ternis.link.
     */
    public function showLink(string $link)
    {
        $link = auth()->user()->links()->with('domain')->findOrFail($link);

        $qrSvg = LinkQrCode::svgDataUri($link);

        return view('dashboard.links.show', compact('link', 'qrSvg'));
    }

    /**
     * Link edit page (Livewire: LinkEditForm).
     *
     * Strictly per-user (see showLink).
     */
    public function editLink(string $link)
    {
        $link = auth()->user()->links()->with('domain')->findOrFail($link);

        return view('dashboard.links.edit', compact('link'));
    }

    /**
     * Export a link's clicks as CSV.
     *
     * Strictly per-user; direct-URL rows are excluded here (they stay
     * visible only in admin-side aggregates).
     */
    public function exportClicks(string $link)
    {
        $user = auth()->user();
        $link = $user->links()->with('domain')->findOrFail($link);

        $clicks = $link->clicks()
            ->where('is_direct_url', false)
            ->orderBy('created_at')
            ->cursor();

        $filename = 'link-'.$link->slug.'-clicks-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($clicks) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['timestamp', 'referrer', 'user_agent', 'country_code', 'city', 'ip_hash']);

            foreach ($clicks as $click) {
                fputcsv($out, [
                    $click->created_at?->toIso8601String(),
                    $click->referrer,
                    $click->user_agent,
                    $click->country_code,
                    $click->city,
                    $click->ip_hash,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Download the link's QR code as PNG (encodes the public short
     * URL). Strictly per-user, like all dashboard routes.
     */
    public function qrCode(string $link)
    {
        $link = auth()->user()->links()->with('domain')->findOrFail($link);

        $filename = 'qr-'.$link->slug.'.png';

        return response(LinkQrCode::png($link), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * API keys management page (Livewire: ApiKeyManager).
     */
    public function apiKeys()
    {
        return view('dashboard.api-keys.index');
    }

    /**
     * Custom domains management page (Livewire: DomainManager).
     */
    public function domains()
    {
        return view('dashboard.domains.index');
    }

    /**
     * Settings page (Livewire: SettingsForm).
     */
    public function settings()
    {
        return view('dashboard.settings.index');
    }

    /**
     * In-app notification inbox (database notifications, newest first).
     */
    public function notifications(Request $request)
    {
        $notifications = $request->user()->notifications()
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('dashboard.notifications.index', compact('notifications'));
    }

    public function markAllNotificationsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return redirect()->route('dashboard.notifications');
    }

    public function markNotificationRead(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        $url = $notification->data['action_url'] ?? null;

        return $url ? redirect()->away($url) : redirect()->route('dashboard.notifications');
    }

    /**
     * Personal activity history: actions the user performed plus
     * actions others (admins, system) performed on their stuff.
     */
    public function activity(Request $request)
    {
        $entries = ActivityLog::visibleTo($request->user()->id)
            ->with(['actor', 'subjectOwner'])
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('dashboard.activity.index', compact('entries'));
    }
}
