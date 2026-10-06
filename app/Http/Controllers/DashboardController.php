<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Click;
use App\Models\Link;
use App\Models\QrGeneration;
use App\Support\DomainUrls;
use App\Support\LinkQrCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    /**
     * Dashboard home — personal overview stats for the signed-in user.
     *
     * System-wide stats live on the admin host (AdminController); this
     * endpoint is strictly per-user, including for admins.
     *
     * Since the my.ternis.link split, href.nz / meinlink.at / href.yt /
     * qr.href.nz links live on the public dashboard — every query here
     * excludes those hostnames (see personalQuery()).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $clicks = Click::whereIn('link_id', $this->personalQuery($user)->select('links.id'))
            ->where('is_direct_url', false);

        $stats = [
            'total_links' => $this->personalQuery($user)->count(),
            'total_clicks' => (clone $clicks)->count(),
            'links_this_month' => $this->personalQuery($user)
                ->where('links.created_at', '>=', now()->startOfMonth())
                ->count(),
            'clicks_today' => (clone $clicks)
                ->where('created_at', '>=', now()->startOfDay())
                ->count(),
        ];

        $publicCount = $user->links()->notRemoved()->whereHas(
            'domain',
            fn (Builder $q) => $q->whereIn('hostname', DomainUrls::publicDashboardHostnames())
        )->count();

        return view('dashboard.index', compact('stats', 'publicCount'));
    }

    /**
     * Links management page (Livewire: LinkTable).
     */
    public function links()
    {
        $publicCount = auth()->user()->links()->notRemoved()->whereHas(
            'domain',
            fn (Builder $q) => $q->whereIn('hostname', DomainUrls::publicDashboardHostnames())
        )->count();

        return view('dashboard.links.index', compact('publicCount'));
    }

    /**
     * Create link page (Livewire: LinkForm).
     */
    public function createLink()
    {
        return view('dashboard.links.create');
    }

    /**
     * CSV import page (Livewire: Dashboard\LinkImport).
     */
    public function importLinks()
    {
        return view('dashboard.links.import');
    }

    /**
     * Link detail + analytics page (Livewire: LinkAnalytics).
     *
     * Strictly per-user: admins manage other users' links from the
     * admin host (Link Moderation), not from dash.ternis.link.
     *
     * `?from_api_key=<ulid>` keeps the back-link (and edit link)
     * inside the per-key page when the user arrived from there.
     */
    public function showLink(Request $request, string $link)
    {
        $link = $this->personalQuery(auth()->user())->with(['domain', 'apiKey:id,name,key_prefix'])->findOrFail($link);

        $qrSvg = LinkQrCode::svgDataUri($link);
        ['backHref' => $backHref, 'backLabel' => $backLabel, 'fromApiKey' => $fromApiKey] = $this->linkBackContext($request);

        return view('dashboard.links.show', compact('link', 'qrSvg', 'backHref', 'backLabel', 'fromApiKey'));
    }

    /**
     * Link edit page (Livewire: LinkEditForm).
     *
     * Strictly per-user (see showLink).
     */
    public function editLink(Request $request, string $link)
    {
        $link = $this->personalQuery(auth()->user())->with(['domain', 'apiKey:id,name,key_prefix'])->findOrFail($link);

        ['backHref' => $backHref, 'backLabel' => $backLabel, 'fromApiKey' => $fromApiKey] = $this->linkBackContext($request);

        return view('dashboard.links.edit', compact('link', 'backHref', 'backLabel', 'fromApiKey'));
    }

    /**
     * Export the user's short links catalog as CSV.
     *
     * Respects optional filters: tag, api_key_id.
     * Strictly per-user; excludes removed links.
     */
    public function exportLinks(Request $request)
    {
        $user = auth()->user();
        $query = $this->personalQuery($user)->with(['domain', 'apiKey:id,name']);

        if ($request->filled('tag')) {
            $tag = strtolower(trim((string) $request->query('tag')));
            $query->where('tags', 'like', '%"'.$tag.'"%');
        }

        if ($request->filled('api_key_id')) {
            $keyId = (string) $request->query('api_key_id');
            if ($keyId === 'none') {
                $query->whereNull('api_key_id');
            } elseif ($user->apiKeys()->whereKey($keyId)->exists()) {
                $query->where('api_key_id', $keyId);
            }
        }

        $links = $query->orderByDesc('created_at')->cursor();

        $filename = 'links-export-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($links) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'slug',
                'short_url',
                'destination_url',
                'domain',
                'api_key',
                'click_count',
                'status',
                'tags',
                'description',
                'created_at',
                'expires_at',
            ]);

            foreach ($links as $link) {
                fputcsv($out, [
                    $link->slug,
                    $link->short_url,
                    $link->destination_url,
                    $link->domain?->hostname ?? config('domains.public_host', 'href.nz'),
                    $link->apiKey?->name ?? 'Dashboard',
                    $link->click_count,
                    $link->is_active && ! $link->isExpired() ? 'active' : ($link->isExpired() ? 'expired' : 'disabled'),
                    $link->tags ? implode(', ', $link->tags) : '',
                    $link->description ?? '',
                    $link->created_at?->toIso8601String(),
                    $link->expires_at?->toIso8601String() ?? '',
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
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
        $link = $this->personalQuery($user)->with('domain')->findOrFail($link);

        $clicks = $link->clicks()
            ->where('is_direct_url', false)
            ->orderBy('created_at')
            ->cursor();

        $filename = 'link-'.$link->slug.'-clicks-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($clicks) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['timestamp', 'referrer', 'user_agent', 'country_code', 'city', 'ip_hash', 'user_identifier', 'tags', 'query_params']);

            foreach ($clicks as $click) {
                fputcsv($out, [
                    $click->created_at?->toIso8601String(),
                    $click->referrer,
                    $click->user_agent,
                    $click->country_code,
                    $click->city,
                    $click->ip_hash,
                    $click->user_identifier,
                    $click->tags ? implode(', ', $click->tags) : null,
                    $click->query_params ? json_encode($click->query_params) : null,
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
        $link = $this->personalQuery(auth()->user())->with('domain')->findOrFail($link);

        $filename = 'qr-'.$link->slug.'.png';
        QrGeneration::create(['link_id' => $link->id, 'format' => 'png']);

        return response(LinkQrCode::png($link), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Download QR codes for all own personal links as a ZIP archive
     * (one print-ready PNG per link, capped at 100 newest).
     */
    public function qrZip()
    {
        $links = $this->personalQuery(auth()->user())
            ->with('domain')
            ->orderByDesc('links.created_at')
            ->limit(100)
            ->get();

        abort_if($links->isEmpty(), 404, 'No links to export.');

        $tmp = tempnam(sys_get_temp_dir(), 'qr-zip-').'.zip';

        $zip = new \ZipArchive;
        $zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($links as $link) {
            $host = $link->domain?->hostname ?? config('domains.public_host', 'href.nz');
            $zip->addFromString('qr-'.$host.'-'.$link->slug.'.png', LinkQrCode::png($link));
        }

        $zip->close();

        return response()->streamDownload(function () use ($tmp) {
            readfile($tmp);
            @unlink($tmp);
        }, 'qr-codes-'.now()->format('Y-m-d').'.zip', ['Content-Type' => 'application/zip']);
    }

    /**
     * API keys management page (Livewire: ApiKeyManager).
     */
    public function apiKeys()
    {
        return view('dashboard.api-keys.index');
    }

    /**
     * Per-key page: every link created with this API key, regardless
     * of its `show_on_dashboard` setting. Strictly per-user.
     */
    public function showApiKey(string $key)
    {
        $apiKey = auth()->user()->apiKeys()->findOrFail($key);

        $stats = [
            'total_links' => $apiKey->links()->notRemoved()->count(),
            'total_clicks' => $apiKey->links()->notRemoved()->sum('click_count'),
        ];

        return view('dashboard.api-keys.show', compact('apiKey', 'stats'));
    }

    /**
     * Custom domains management page (Livewire: DomainManager).
     */
    public function domains()
    {
        return view('dashboard.domains.index');
    }

    /**
     * Link-in-bio pages (Livewire: Bio\PageBuilder).
     */
    public function bio()
    {
        return view('dashboard.bio.index');
    }

    /**
     * Bio page detail + per-button analytics (Livewire: Bio\PageAnalytics).
     */
    public function showBio(string $page)
    {
        $page = auth()->user()->bioPages()->with(['domain:id,hostname', 'buttons', 'children'])->where('is_removed', false)->findOrFail($page);

        return view('dashboard.bio.show', compact('page'));
    }

    /**
     * Visual page-builder: phone preview, drag-and-drop buttons,
     * sub-page tabs, settings (Livewire: Bio\VisualBuilder).
     */
    public function buildBio(string $page)
    {
        $page = auth()->user()->bioPages()->with(['domain:id,hostname'])->where('is_removed', false)->findOrFail($page);

        return view('dashboard.bio.build', compact('page'));
    }

    /**
     * Download the page's QR code as PNG (encodes the public page URL).
     * Strictly per-user, like all dashboard routes.
     */
    public function bioQr(string $page)
    {
        $page = auth()->user()->bioPages()->with('domain')->findOrFail($page);

        $host = $page->domain?->hostname ?? config('domains.public_host', 'href.nz');
        $url = 'https://'.$host.($page->parent_id === null ? '/' : '/'.$page->slug);
        QrGeneration::create(['format' => 'png']);

        return response(LinkQrCode::pngForUrl($url), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="bio-qr-'.preg_replace('/[^a-z0-9-]+/i', '-', $page->title).'.png"',
        ]);
    }

    /**
     * Settings page (Livewire: SettingsForm).
     */
    public function settings()
    {
        return view('dashboard.settings.index');
    }

    /**
     * Download an own privacy export ZIP (owner only, before expiry).
     */
    public function downloadExport(string $export)
    {
        $user = auth()->user();
        $export = $user->privacyExports()->findOrFail($export);

        if ($export->status !== 'done' || ! $export->path
            || ! Storage::disk('local')->exists($export->path)) {
            abort(404, 'Export not ready.');
        }

        if ($export->expires_at && $export->expires_at->isPast()) {
            abort(410, 'Export expired.');
        }

        return Storage::disk('local')->download($export->path, 'ternis-export.zip');
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

    /**
     * Personal link scope: own non-removed links EXCLUDING the public
     * dashboard hostnames (href.nz, meinlink.at, href.yt, qr.href.nz —
     * those live on my.ternis.link). Links without a domain row stay here.
     *
     * @return HasMany|Builder
     */
    private function personalQuery($user): object
    {
        return $user->links()->notRemoved()->where(function (Builder $q) {
            $q->whereDoesntHave('domain')
                ->orWhereHas(
                    'domain',
                    fn (Builder $qq) => $qq->whereNotIn('hostname', DomainUrls::publicDashboardHostnames())
                );
        });
    }

    /**
     * Back-link context for link detail/edit pages. When `from_api_key`
     * names an owned key, point back at its per-key page; otherwise
     * fall back to the main links list. Returns the href, label, and
     * the validated key id (for forwarding to edit/analytics links).
     *
     * @return array{backHref: string, backLabel: string, fromApiKey: ?string}
     */
    private function linkBackContext(Request $request): array
    {
        $fromApiKey = $request->query('from_api_key');

        if (is_string($fromApiKey) && $fromApiKey !== ''
            && auth()->user()->apiKeys()->whereKey($fromApiKey)->exists()) {
            return [
                'backHref' => route('dashboard.api-keys.show', $fromApiKey),
                'backLabel' => 'Back to API key links',
                'fromApiKey' => $fromApiKey,
            ];
        }

        return [
            'backHref' => route('dashboard.links'),
            'backLabel' => 'Back to Links',
            'fromApiKey' => null,
        ];
    }
}
