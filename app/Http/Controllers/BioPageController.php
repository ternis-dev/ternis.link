<?php

namespace App\Http\Controllers;

use App\Models\BioButton;
use App\Models\BioPage;
use App\Models\Domain;
use App\Services\BioTrackerService;
use App\Services\CrawlerDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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

        if (! $button->isLive() || $button->kind === 'divider' || $button->kind === 'header') {
            abort(404);
        }

        if ($button->destination_url === null) {
            abort(404);
        }

        $this->tracker->trackTap($button->page, $button, $request);

        return redirect()->away($button->destination_url, 302);
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

        if (! $page->isVisible()) {
            abort(404);
        }

        $buttons = $page->buttons()->where('is_active', true)->orderBy('sort_order')->get()->filter->isLive()->values();
        $subs = $root ? $root->children()->where('is_removed', false)->where('is_active', true)->orderBy('sort_order')->get() : collect();

        $og = [
            'title' => $page->og_title ?? $page->title,
            'description' => $page->og_description ?? $page->bio,
            'image' => $page->og_image_url ?? $page->avatar_url,
        ];

        if (! $this->crawlers->isCrawler($request->userAgent())) {
            $this->tracker->trackView($page, $request);
        }

        return response()->view('bio.show', [
            'page' => $page->load('domain'),
            'root' => $root?->load('domain'),
            'buttons' => $buttons,
            'subs' => $subs,
            'og' => $og,
            'domain' => $page->domain ?? $root?->domain,
        ], 200, ['Cache-Control' => 'public, max-age=60']);
    }
}
