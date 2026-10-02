<?php

namespace App\Http\Controllers;

use App\Models\BioButton;
use App\Models\BioEvent;
use App\Models\BioPage;
use App\Models\Domain;
use App\Services\BioService;
use App\Services\BioTrackerService;
use App\Services\CrawlerDetector;
use App\Support\IpHash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class BioPageController extends Controller
{
    public function __construct(
        private BioTrackerService $tracker,
        private CrawlerDetector $crawlers,
    ) {}

    public function showRoot(Request $request)
    {
        $domain = $request->attributes->get('domain_model');

        if (! $domain instanceof Domain || $domain->isSystemDomain()) {
            abort(404);
        }

        $page = $this->rootFor($domain);

        if (! $page) {
            abort(404);
        }

        return $this->renderPage($request, $page);
    }

    public function showSub(Request $request, string $sub)
    {
        $domain = $request->attributes->get('domain_model');

        if (! $domain instanceof Domain || $domain->isSystemDomain()) {
            abort(404);
        }

        $root = $this->rootFor($domain);

        if (! $root) {
            abort(404);
        }

        $page = BioPage::where('parent_id', $root->id)
            ->where('slug', strtolower($sub))
            ->where('is_removed', false)
            ->first();

        if (! $page) {
            abort(404);
        }

        return $this->renderPage($request, $page, $root);
    }

    public function tap(Request $request, string $button)
    {
        $button = BioButton::with('page.domain')->find($button);

        if (! $button || ! $button->page || $button->page->is_removed) {
            abort(404);
        }

        // Locked pages leak nothing: no destinations, no taps.
        if ($this->isLocked($button->page, $request)) {
            abort(404);
        }

        // Expired, deactivated, or scheduled pages resolve nothing.
        if (! $button->page->isVisible()) {
            abort(404);
        }

        if (! $button->isLive() || $button->kind === 'divider' || $button->kind === 'header') {
            abort(404);
        }

        // Contact buttons download a vCard (tracked like a tap).
        if ($button->kind === 'contact') {
            $this->tracker->trackTap($button->page, $button, $request);

            $filename = preg_replace('/[^a-z0-9]+/i', '-', strtolower($button->modal_title ?: $button->label)).'.vcf';

            return response(BioService::vcard($button), 200, [
                'Content-Type' => 'text/vcard; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        }

        // Modal pop-ups, coupons, and RSVPs open client-side; a crafted
        // GET lands back on the page, untracked.
        if ($button->action === 'modal' || in_array($button->kind, ['coupon', 'rsvp'], true)) {
            return redirect()->away($this->pageUrl($button->page), 302);
        }

        if ($button->action === 'subpage') {
            $target = $button->targetPage;

            if (! $target || ! $target->isVisible()) {
                abort(404);
            }

            $this->tracker->trackTap($button->page, $button, $request);

            $host = $button->page->domain?->hostname ?? $request->getHost();

            return redirect()->away("https://{$host}/{$target->slug}", 302);
        }

        if ($button->destination_url === null) {
            abort(404);
        }

        $this->tracker->trackTap($button->page, $button, $request);

        return redirect()->away($button->destination_url, 302);
    }

    /**
     * Tracking pixel for client-side opens (modal pop-ups, video plays,
     * coupon copies). GET so no CSRF token is needed.
     */
    public function openPixel(Request $request, string $button)
    {
        $button = BioButton::with('page')->find($button);

        if ($button && $button->page && ! $button->page->is_removed
            && $button->page->isVisible()
            && ! $this->isLocked($button->page, $request)
            && $button->isLive()
            && ($button->action === 'modal' || in_array($button->kind, ['video', 'coupon'], true))) {
            $this->tracker->trackTap($button->page, $button, $request);
        }

        $pixel = base64_decode('R0lGODlhAQABAIAAAP///////yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==');

        return response($pixel, 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store',
            'Content-Length' => strlen($pixel),
        ]);
    }

    /**
     * RSVP headcount for event blocks (one per visitor hash, no PII
     * collected). GET so it works without JavaScript.
     */
    public function rsvp(Request $request, string $button)
    {
        $button = BioButton::with('page.domain')->find($button);

        if (! $button || ! $button->page || $button->page->is_removed
            || ! $button->page->isVisible()
            || self::isLocked($button->page, $request)
            || ! $button->isLive() || $button->kind !== 'rsvp') {
            abort(404);
        }

        if (! $this->tracker->hasRsvpd($button, $request)) {
            $this->tracker->trackRsvp($button->page, $button, $request);
        }

        return redirect()->away($this->pageUrl($button->page).'?rsvpd=1', 302);
    }

    /**
     * Signed draft preview: renders an unpublished page in-action on
     * its own domain (no login needed, signature is the auth). Never
     * tracked, never indexed.
     */
    public function draft(Request $request, string $page)
    {
        $page = BioPage::with(['domain', 'children'])->findOrFail($page);

        if ($page->is_removed) {
            abort(404);
        }

        $buttons = $page->buttons()->orderBy('sort_order')->get()->filter->isLive()->values();
        $root = $page->parent_id === null ? $page : $page->parent;
        $subs = $root ? $root->children()->where('is_removed', false)->where('is_active', true)->orderBy('sort_order')->get() : collect();

        return response()->view('bio.show', [
            'page' => $page->load('domain'),
            'root' => $root?->load('domain'),
            'buttons' => $buttons,
            'subs' => $subs,
            'og' => [
                'title' => $page->og_title ?? $page->title,
                'description' => $page->og_description ?? $page->bio,
                'image' => $page->og_image_url ?? $page->avatar_url,
            ],
            'domain' => $page->domain ?? $root?->domain,
            'draft' => true,
        ], 200, ['Cache-Control' => 'no-store', 'X-Robots-Tag' => 'noindex']);
    }

    private function pageUrl(BioPage $page): string
    {
        $host = $page->domain?->hostname ?? request()->getHost();
        $path = $page->parent_id === null ? '/' : '/'.$page->slug;

        return "https://{$host}{$path}";
    }

    public static function isLocked(BioPage $page, Request $request): bool
    {
        return $page->password_hash !== null
            && $request->session()->get(BioService::sessionKey($page->id)) !== true;
    }

    /**
     * Unlock a password-protected page for this session.
     * Throttled (see route) so passwords can't be brute-forced.
     */
    public function unlock(Request $request, string $page)
    {
        $domain = $request->attributes->get('domain_model');

        if (! $domain instanceof Domain || $domain->isSystemDomain()) {
            abort(404);
        }

        $page = BioPage::where('id', $page)
            ->where('is_removed', false)
            ->where(fn ($q) => $q->where('domain_id', $domain->id)->orWhereHas('parent', fn ($p) => $p->where('domain_id', $domain->id)))
            ->first();

        if (! $page || $page->password_hash === null) {
            abort(404);
        }

        $password = (string) $request->input('password', '');

        if (! Hash::check($password, $page->password_hash)) {
            return back()->withErrors(['password' => 'Wrong password — try again.']);
        }

        $request->session()->put(BioService::sessionKey($page->id), true);

        return redirect()->away($this->pageUrl($page), 302);
    }

    private function rootFor(Domain $domain): ?BioPage
    {
        $key = BioPage::cacheKeyRoot($domain->id);
        $cached = Cache::get($key);

        if (is_array($cached)) {
            $page = BioPage::hydrate([$cached])->first();
            if ($page && $page->isVisible()) {
                return $page;
            }
            Cache::forget($key);
        }

        $page = BioPage::where('domain_id', $domain->id)
            ->whereNull('parent_id')
            ->where('is_removed', false)
            ->where('is_active', true)
            ->first();

        if ($page) {
            Cache::put($key, $page->getAttributes(), 300);
        }

        return $page;
    }

    private function renderPage(Request $request, BioPage $page, ?BioPage $root = null)
    {
        $root ??= $page->parent_id === null ? $page : $page->parent;

        // Expired pages with a destination hand off instead of 404ing
        // (event pages → follow-up page). Untracked: the page is gone.
        if ($page->isExpired() && $page->gone_url) {
            return redirect()->away($page->gone_url, 302);
        }

        if (! $page->isVisible()) {
            abort(404);
        }

        // Password-protected pages show an interstitial: title only,
        // no buttons, no tracking, no indexing.
        if (self::isLocked($page, $request)) {
            return response()->view('bio.locked', [
                'page' => $page->load('domain'),
            ], 200, ['Cache-Control' => 'no-store', 'X-Robots-Tag' => 'noindex']);
        }

        $buttons = $page->buttons()->where('is_active', true)->orderBy('sort_order')->get()->filter->isLive()->values();
        $subs = $root ? $root->children()->where('is_removed', false)->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->orderBy('sort_order')->get() : collect();

        $og = [
            'title' => $page->og_title ?? $page->title,
            'description' => $page->og_description ?? $page->bio,
            'image' => $page->og_image_url ?? $page->avatar_url,
        ];

        if (! $this->crawlers->isCrawler($request->userAgent())) {
            $this->tracker->trackView($page, $request);
        }

        // RSVP state per button for this visitor (single indexed query).
        $rsvpd = [];
        $rsvpButtons = $buttons->filter(fn ($b) => $b->kind === 'rsvp');
        if ($rsvpButtons->isNotEmpty()) {
            $hash = IpHash::make($request->ip());
            if ($hash !== null) {
                $rsvpd = BioEvent::whereIn('bio_button_id', $rsvpButtons->pluck('id'))
                    ->where('kind', 'rsvp')
                    ->where('ip_hash', $hash)
                    ->pluck('bio_button_id')
                    ->all();
            }
        }

        return response()->view('bio.show', [
            'page' => $page->load('domain'),
            'root' => $root?->load('domain'),
            'buttons' => $buttons,
            'subs' => $subs,
            'og' => $og,
            'domain' => $page->domain ?? $root?->domain,
            'rsvpd' => $rsvpd,
            // Per-visitor RSVP state must never sit in a shared cache.
        ], 200, ['Cache-Control' => $rsvpButtons->isNotEmpty() ? 'private, max-age=60' : 'public, max-age=60']);
    }
}
