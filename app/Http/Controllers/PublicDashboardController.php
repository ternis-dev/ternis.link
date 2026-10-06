<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesPublicLinks;
use App\Models\Click;
use App\Models\QrGeneration;
use App\Services\LinkService;
use App\Support\LinkQrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Public links dashboard (my.ternis.link) — the authenticated home for
 * href.nz + meinlink.at + href.yt (+ qr.href.nz) links.
 *
 * Strict hostname partition with dash.ternis.link (clicked.at,
 * ternis.link, href.re, partner and custom domains stay there; links
 * without a domain row stay there too).
 *
 * Account-level sections (API keys, domains, bio, notifications,
 * activity, settings) stay single-homed on dash.ternis.link; this
 * controller serves overview + links CRUD + per-link analytics only.
 *
 * View/route prefixes make the legacy dashboard a thin subclass; the
 * queries and exports below serve both UIs unchanged.
 */
class PublicDashboardController extends Controller
{
    use ScopesPublicLinks;

    protected string $views = 'public-dashboard';

    protected string $routes = 'public-dashboard';

    /**
     * Overview stats for the signed-in user's public shortener links.
     * Legacy opt-ins bounce to the legacy home instead.
     */
    public function index(Request $request)
    {
        if ($request->user()->public_dashboard_legacy) {
            return redirect()->route('public-dashboard.legacy');
        }

        return view($this->views.'.index', $this->overview($request->user()));
    }

    /**
     * @return array{stats: array, byDomain: Collection, expiringSoon: Collection, topLinks: Collection, recentClicks: Collection}
     */
    protected function overview(object $user): array
    {
        $base = $this->publicLinksQuery($user);
        $clicks = Click::whereIn('link_id', (clone $base)->select('links.id'))
            ->where('is_direct_url', false);

        $stats = [
            'total_links' => (clone $base)->count(),
            'total_clicks' => (clone $clicks)->count(),
            'links_this_month' => (clone $base)
                ->where('links.created_at', '>=', now()->startOfMonth())
                ->count(),
            'clicks_today' => (clone $clicks)
                ->where('clicks.created_at', '>=', now()->startOfDay())
                ->count(),
        ];

        $byDomain = (clone $base)
            ->join('domains', 'domains.id', '=', 'links.domain_id')
            ->selectRaw('domains.hostname, count(*) as link_count, coalesce(sum(links.click_count), 0) as click_sum')
            ->groupBy('domains.hostname')
            ->orderByDesc('link_count')
            ->get();

        $expiringSoon = (clone $base)
            ->with('domain')
            ->where('links.is_active', true)
            ->where('links.expires_at', '>', now())
            ->where('links.expires_at', '<=', now()->addDays(7))
            ->orderBy('links.expires_at')
            ->limit(5)
            ->get();

        $topLinks = (clone $base)
            ->with('domain')
            ->where('links.click_count', '>', 0)
            ->orderByDesc('links.click_count')
            ->limit(5)
            ->get();

        $recentClicks = Click::whereIn('link_id', (clone $base)->select('links.id'))
            ->where('is_direct_url', false)
            ->with('link.domain')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return compact('stats', 'byDomain', 'expiringSoon', 'topLinks', 'recentClicks');
    }

    /**
     * Opt into the legacy dashboard and land on its home.
     */
    public function switchToLegacy(Request $request)
    {
        $request->user()->update(['public_dashboard_legacy' => true]);

        return redirect()->route('public-dashboard.legacy');
    }

    /**
     * Links management page (Livewire: LinkTable scoped to public hosts).
     */
    public function links()
    {
        return view($this->views.'.links.index');
    }

    /**
     * Create link page (Livewire: LinkForm scoped to public hosts).
     */
    public function createLink()
    {
        return view($this->views.'.links.create');
    }

    /**
     * CSV import page (Livewire: Dashboard\LinkImport scoped to public hosts).
     */
    public function importLinks()
    {
        return view($this->views.'.links.import');
    }

    /**
     * Per-key page: public links created with this API key, regardless
     * of its `show_on_dashboard` setting. Strictly per-user and scoped
     * to public hostnames; key management itself stays on dash.
     */
    public function showApiKey(string $key)
    {
        $apiKey = auth()->user()->apiKeys()->findOrFail($key);

        $links = $this->publicLinksQuery(auth()->user())->where('links.api_key_id', $apiKey->id);

        $stats = [
            'total_links' => (clone $links)->count(),
            'total_clicks' => (clone $links)->sum('links.click_count'),
        ];

        return view($this->views.'.api-keys.show', compact('apiKey', 'stats'));
    }

    /**
     * Link detail + analytics page (Livewire: LinkAnalytics).
     *
     * Strictly per-user AND scoped to public hostnames: ternis/business
     * links 404 here (manage them on dash.ternis.link).
     */
    public function showLink(Request $request, string $link)
    {
        $link = $this->publicLinksQuery(auth()->user())
            ->with(['domain', 'apiKey:id,name,key_prefix'])
            ->findOrFail($link);

        $qrSvg = LinkQrCode::svgDataUri($link);
        ['backHref' => $backHref, 'backLabel' => $backLabel] = $this->linkBackContext($request);

        return view($this->views.'.links.show', compact('link', 'qrSvg', 'backHref', 'backLabel'));
    }

    /**
     * Link edit page (Livewire: LinkEditForm). Same scope as showLink.
     */
    public function editLink(Request $request, string $link)
    {
        $link = $this->publicLinksQuery(auth()->user())
            ->with(['domain', 'apiKey:id,name,key_prefix'])
            ->findOrFail($link);

        ['backHref' => $backHref, 'backLabel' => $backLabel] = $this->linkBackContext($request);

        return view($this->views.'.links.edit', compact('link', 'backHref', 'backLabel'));
    }

    /**
     * Duplicate a link (fresh slug, same setup) and continue on its
     * edit page. Scoped like showLink.
     */
    public function duplicate(string $link)
    {
        $link = $this->publicLinksQuery(auth()->user())->with('domain')->findOrFail($link);

        $copy = app(LinkService::class)->duplicate($link, auth()->user());

        return redirect()->route($this->routes.'.links.edit', $copy->id)
            ->with('info', "Duplicated as {$copy->domain->hostname}/{$copy->slug} — set a slug, expiry, or password to finish.");
    }

    /**
     * Export the user's public shortener catalog as CSV.
     */
    public function exportLinks()
    {
        $links = $this->publicLinksQuery(auth()->user())
            ->with(['domain', 'apiKey:id,name'])
            ->orderByDesc('links.created_at')
            ->cursor();

        $filename = 'public-links-export-'.now()->format('Y-m-d').'.csv';

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
     * Export a link's clicks as CSV (scoped like showLink).
     */
    public function exportClicks(string $link)
    {
        $link = $this->publicLinksQuery(auth()->user())->with('domain')->findOrFail($link);

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
     * Download the link's QR code as PNG (scoped like showLink).
     */
    public function qrCode(string $link)
    {
        $link = $this->publicLinksQuery(auth()->user())->with('domain')->findOrFail($link);

        $filename = 'qr-'.$link->slug.'.png';
        QrGeneration::create(['link_id' => $link->id, 'format' => 'png']);

        return response(LinkQrCode::png($link), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Download QR codes for all own public links as a ZIP archive
     * (one print-ready PNG per link, capped at 100 newest).
     */
    public function qrZip()
    {
        $links = $this->publicLinksQuery(auth()->user())
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
     * Download an own privacy export ZIP (owner only, before expiry).
     * Served on both dashboards so public-only users are not stranded.
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
     * Back-link context for link detail/edit pages. When `from_api_key`
     * names an owned key, point back at its per-key page on this UI;
     * otherwise fall back to the main links list.
     *
     * @return array{backHref: string, backLabel: string}
     */
    protected function linkBackContext(Request $request): array
    {
        $fromApiKey = $request->query('from_api_key');

        if (is_string($fromApiKey) && $fromApiKey !== ''
            && auth()->user()->apiKeys()->whereKey($fromApiKey)->exists()) {
            return [
                'backHref' => route($this->routes.'.api-keys.show', $fromApiKey),
                'backLabel' => 'Back to API key links',
            ];
        }

        return [
            'backHref' => route($this->routes.'.links'),
            'backLabel' => 'Back to Links',
        ];
    }
}
