<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\RecordQrGeneration;
use App\Models\Domain;
use App\Services\LinkService;
use App\Services\SlugResolverService;
use App\Support\LinkQrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class PublicQrCodeController extends Controller
{
    public const FORMATS = ['svg', 'png'];

    public function __construct(
        private SlugResolverService $slugs,
        private LinkService $links,
    ) {}

    /**
     * GET /v1/qr — Generate a QR code for an arbitrary public URL.
     *
     * SVG is the default format; PNG can be requested with ?format=png.
     */
    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'url' => ['required', 'url:http,https', 'max:2048'],
            'format' => ['nullable', Rule::in(self::FORMATS)],
        ]);

        $format = $validated['format'] ?? 'svg';

        RecordQrGeneration::dispatch(null, $format);

        return $this->render($validated['url'], $format);
    }

    /**
     * GET /qr/{url} — Pretty QR code, PNG by default (public hosts).
     * Accepts bare hostnames (example.com/…) as well as full URLs.
     */
    public function pretty(Request $request, string $url): Response
    {
        RecordQrGeneration::dispatch(null, 'png');

        return $this->render($this->cleanUrl($url), 'png');
    }

    /**
     * GET /qr/{url}/{mime} — Pretty QR code in the requested format.
     */
    public function prettyMime(Request $request, string $url, string $mime): Response
    {
        RecordQrGeneration::dispatch(null, $mime);

        return $this->render($this->cleanUrl($url), $mime);
    }

    /**
     * GET /{url}.{mime} — QR code for a short-link slug or a direct
     * URL (public hosts). Slug-shaped input resolves the short link
     * first; anything URL-shaped encodes the destination directly.
     * Unknown slugs 404 like a normal miss.
     */
    public function suffixed(Request $request, string $url, string $mime): Response
    {
        if ($this->slugs->classify($url) === 'slug') {
            $domain = $request->attributes->get('domain_model');

            if (! $domain) {
                $domain = Domain::where('hostname', 'href.nz')->first();
            }

            $link = $domain ? $this->links->resolveSlug($url, $domain) : null;

            if (! $link) {
                abort(404);
            }

            RecordQrGeneration::dispatch($link->id, $mime);

            return $this->render(LinkQrCode::shortUrl($link), $mime);
        }

        RecordQrGeneration::dispatch(null, $mime);

        return $this->render($this->cleanUrl($url), $mime);
    }

    /**
     * GET /{slug}/qr — QR code for a short-link slug, PNG by default.
     */
    public function slugQr(Request $request, string $slug): Response
    {
        return $this->slugQrMime($request, $slug, 'png');
    }

    /**
     * GET /{slug}/qr.{mime} — QR code for a short-link slug in the
     * requested format. Unknown slugs 404 like a normal miss.
     */
    public function slugQrMime(Request $request, string $slug, string $mime): Response
    {
        $domain = $request->attributes->get('domain_model');

        if (! $domain) {
            $domain = Domain::where('hostname', 'href.nz')->first();
        }

        $link = $domain ? $this->links->resolveSlug($slug, $domain) : null;

        if (! $link) {
            abort(404);
        }

        RecordQrGeneration::dispatch($link->id, $mime);

        return $this->render(LinkQrCode::shortUrl($link), $mime);
    }

    /**
     * Normalize (bare hostnames gain https://) and validate against
     * the same rules as the v1 endpoint, so error shapes stay
     * identical (422 JSON).
     */
    private function cleanUrl(string $url): string
    {
        return Validator::make(
            ['url' => $this->slugs->normalizeUrl($url)],
            ['url' => ['required', 'url:http,https', 'max:2048']]
        )->validate()['url'];
    }

    private function render(string $url, string $format): Response
    {
        abort_unless(in_array($format, self::FORMATS, true), 404);

        $extension = $format;

        if ($format === 'png') {
            return response(LinkQrCode::pngForUrl($url), 200, [
                'Content-Type' => 'image/png',
                'Content-Disposition' => 'inline; filename="qr.'.$extension.'"',
            ]);
        }

        return response(LinkQrCode::svgForUrl($url), 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'inline; filename="qr.'.$extension.'"',
        ]);
    }
}
