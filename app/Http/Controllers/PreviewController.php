<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Link;
use App\Models\ThreatEvaluation;
use App\Services\JunkUrlDetector;
use App\Services\LinkService;
use App\Services\SlugResolverService;
use Illuminate\Http\Request;

/**
 * GET /preview/{input} on href.nz — link preview sandbox.
 *
 * Shows what a short link (or any direct URL) points to WITHOUT
 * opening it: no redirect, no tracking row, no click counted. The
 * visitor decides with full information, including a scanner-probe
 * verdict from the junk detector. href.nz only — 404 elsewhere.
 */
class PreviewController extends Controller
{
    public function __construct(
        private SlugResolverService $slugResolver,
        private LinkService $linkService,
        private JunkUrlDetector $junkUrls,
    ) {}

    public function show(Request $request, string $input)
    {
        $publicHost = (string) config('domains.public_host', 'href.nz');
        $hostname = explode(':', (string) $request->getHost())[0];

        if ($hostname !== $publicHost) {
            abort(404);
        }

        $domain = $request->attributes->get('domain_model');

        if (! $domain instanceof Domain) {
            $domain = Domain::where('hostname', $publicHost)->first();
        }

        if (! $domain) {
            abort(404);
        }

        if ($this->slugResolver->classify($input) === 'url') {
            $url = $this->slugResolver->normalizeUrl($input);

            if (! filter_var($url, FILTER_VALIDATE_URL)) {
                return response()->view('redirect.not-found', ['slug' => $input, 'domain' => $hostname], 404);
            }

            $threat = ThreatEvaluation::where('destination_url', $url)->latest()->first();

            return view('preview.show', [
                'mode' => 'url',
                'link' => null,
                'destination' => $url,
                'host' => (string) parse_url($url, PHP_URL_HOST),
                'junkReasons' => $this->junkUrls->reasons($url),
                'threat' => $threat,
                'og' => ['title' => (string) parse_url($url, PHP_URL_HOST), 'description' => null, 'image' => null],
            ]);
        }

        $link = $this->linkService->resolveSlug($input, $domain);

        if (! $link) {
            // Check if link exists but is inactive / quarantined so the preview sandbox can display its security status
            $link = Link::where('domain_id', $domain->id)
                ->where('slug', $input)
                ->where('is_removed', false)
                ->first();
        }

        if (! $link) {
            return response()->view('redirect.not-found', ['slug' => $input, 'domain' => $domain->hostname], 404);
        }

        // Password-protected links stay opaque here too — the sandbox
        // must not become a destination oracle.
        if ($link->password_hash !== null
            && $request->session()->get(LinkService::sessionKey($link->id)) !== true) {
            return response()->view('redirect.locked', [
                'slug' => $link->slug,
                'domain' => $domain->hostname,
                'link' => $link->id,
            ], 200, ['Cache-Control' => 'no-store', 'X-Robots-Tag' => 'noindex']);
        }

        $link->load(['domain', 'threatEvaluations' => fn ($q) => $q->latest()]);
        $threat = $link->threatEvaluations->first();
        if (! $threat && $link->destination_url) {
            $threat = ThreatEvaluation::where('destination_url', (string) $link->destination_url)->latest()->first();
        }

        return view('preview.show', [
            'mode' => 'slug',
            'link' => $link,
            'destination' => LinkService::destinationWithUtm($link),
            'host' => (string) parse_url((string) $link->destination_url, PHP_URL_HOST),
            'junkReasons' => $this->junkUrls->reasons((string) $link->destination_url),
            'threat' => $threat,
            'og' => $link->effectiveSocialPreview(),
        ]);
    }
}
