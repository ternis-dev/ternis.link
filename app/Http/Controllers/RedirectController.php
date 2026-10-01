<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Services\ClickTrackerService;
use App\Services\CrawlerDetector;
use App\Services\JunkUrlDetector;
use App\Services\LinkService;
use App\Services\SlugResolverService;
use App\Services\TargetSelector;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    public function __construct(
        private SlugResolverService $slugResolver,
        private LinkService $linkService,
        private ClickTrackerService $clickTracker,
        private JunkUrlDetector $junkUrls,
        private CrawlerDetector $crawlers,
        private TargetSelector $targets,
    ) {}

    /**
     * Handle /url/{url} — preferred direct URL redirect.
     * Clicks are stored but only visible to admins.
     *
     * Scanner probes are still redirected (it's a redirector) but
     * never get a tracking row — that's what polluted the admin
     * with u_* junk links.
     */
    public function directUrl(Request $request, string $url)
    {
        $normalizedUrl = $this->slugResolver->normalizeUrl($url);
        $domain = $request->attributes->get('domain_model');

        if (! $domain) {
            $domain = Domain::where('hostname', 'href.nz')->first();
        }

        if ($domain && ! $this->junkUrls->isJunk($normalizedUrl)) {
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
     * Handle /{input} — bio sub-page first (custom partner domains),
     * then URL-vs-slug redirect as before.
     */
    public function resolve(Request $request, string $input)
    {
        // Bio sub-pages win over short links on custom partner domains
        // (first-write-wins is enforced at creation: slugs colliding
        // either way get 422, so this is just a read-order choice).
        if (preg_match('/^[a-z0-9-]{1,64}$/', strtolower($input))) {
            $domain = $request->attributes->get('domain_model');

            if ($domain instanceof Domain && ! $domain->isSystemDomain()) {
                try {
                    return app(BioPageController::class)->showSub($request, $input);
                } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    // No sub-page — fall through to slug redirect below.
                }
            }
        }

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
            return response()->view('redirect.not-found', ['slug' => $input, 'domain' => request()->getHost()], 404);
        }

        $link = $this->linkService->resolveSlug($input, $domain);

        if (! $link) {
            return response()->view('redirect.not-found', ['slug' => $input, 'domain' => $domain->hostname], 404);
        }

        $isCrawler = $this->crawlers->isCrawler($request->userAgent());
        $debugOg = $request->query('debug') === 'og';

        if ($link->hasSocialPreview() && ($isCrawler || $debugOg)) {
            // Crawlers (and ?debug=og) get a fast HTML stub — no click counted.
            return response()
                ->view('redirect.preview-stub', [
                    'link' => $link->load('domain'),
                    'og' => $link->effectiveSocialPreview(),
                    'destination' => (string) $link->destination_url,
                ], 200, ['Cache-Control' => 'public, max-age=300'])
                ->header('Vary', 'User-Agent');
        }

        $debugTarget = $request->query('target') === 'debug';
        $target = $this->targets->pick($link, $request);
        $destination = $target?->destination_url ?? (string) $link->destination_url;

        if ($debugTarget) {
            return response()->json([
                'slug' => $link->slug,
                'destination' => $destination,
                'target_id' => $target?->id,
                'target_label' => $target?->label,
            ]);
        }

        $this->clickTracker->track($link, $request, target: $target);

        return redirect()->away($destination, 302);
    }
}
