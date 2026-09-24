<?php

namespace App\Http\Controllers;

use App\Models\Domain;
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

            return view('preview.show', [
                'mode' => 'url',
                'link' => null,
                'destination' => $url,
                'host' => (string) parse_url($url, PHP_URL_HOST),
                'junkReasons' => $this->junkUrls->reasons($url),
            ]);
        }

        $link = $this->linkService->resolveSlug($input, $domain);

        if (! $link) {
            return response()->view('redirect.not-found', ['slug' => $input, 'domain' => $domain->hostname], 404);
        }

        return view('preview.show', [
            'mode' => 'slug',
            'link' => $link->load('domain'),
            'destination' => (string) $link->destination_url,
            'host' => (string) parse_url((string) $link->destination_url, PHP_URL_HOST),
            'junkReasons' => $this->junkUrls->reasons((string) $link->destination_url),
        ]);
    }
}
