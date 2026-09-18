<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Services\ClickTrackerService;
use App\Services\LinkService;
use App\Services\SlugResolverService;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    public function __construct(
        private SlugResolverService $slugResolver,
        private LinkService $linkService,
        private ClickTrackerService $clickTracker,
    ) {}

    /**
     * Handle /url/{url} — preferred direct URL redirect.
     * Clicks are stored but only visible to admins.
     */
    public function directUrl(Request $request, string $url)
    {
        $normalizedUrl = $this->slugResolver->normalizeUrl($url);
        $domain = $request->attributes->get('domain_model');

        if (! $domain) {
            $domain = Domain::where('hostname', 'href.nz')->first();
        }

        if ($domain) {
            $link = $this->linkService->findOrCreateDirectUrlLink($normalizedUrl, $domain);
            $this->clickTracker->track($link, $request, isDirectUrl: true);
        }

        return redirect()->away($normalizedUrl, 302);
    }

    /**
     * Handle /go/{url} — alternative direct URL redirect.
     */
    public function goUrl(Request $request, string $url)
    {
        return $this->directUrl($request, $url);
    }

    /**
     * Handle /{input} — detect if URL or slug, then redirect.
     */
    public function resolve(Request $request, string $input)
    {
        $type = $this->slugResolver->classify($input);

        if ($type === 'url') {
            return $this->directUrl($request, $input);
        }

        // It's a slug — look it up
        $domain = $request->attributes->get('domain_model');

        if (! $domain) {
            $domain = Domain::where('hostname', 'href.nz')->first();
        }

        if (! $domain) {
            return response()->view('redirect.not-found', ['slug' => $input], 404);
        }

        $link = $this->linkService->resolveSlug($input, $domain);

        if (! $link) {
            return response()->view('redirect.not-found', ['slug' => $input], 404);
        }

        $this->clickTracker->track($link, $request);

        return redirect()->away($link->destination_url, 302);
    }
}
