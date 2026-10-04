<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\QrGeneration;
use App\Services\LinkService;
use App\Services\QrCodeService;
use App\Services\SlugResolverService;
use App\Support\LinkQrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class PublicQrCodeController extends Controller
{
    public const FORMATS = ['svg', 'png', 'json'];

    public function __construct(
        private SlugResolverService $slugs,
        private LinkService $links,
        private QrCodeService $qrService,
    ) {}

    /**
     * GET /v1/qr, POST /v1/qr — Generate a QR code with full type and style support.
     *
     * Backward-compatible with existing url query param; supports all 12 types.
     */
    public function __invoke(Request $request): Response
    {
        $type = $request->input('type', $request->query('type'));

        if ($type && in_array(strtolower($type), QrCodeService::SUPPORTED_TYPES, true) && strtolower($type) !== 'url') {
            return $this->qrService->apiResponse($request, strtolower($type), $request->all());
        }

        if ($request->has('url')) {
            $validated = $request->validate([
                'url' => ['required', 'url:http,https', 'max:2048'],
                'format' => ['nullable', Rule::in(self::FORMATS)],
            ]);

            return $this->qrService->apiResponse($request, 'url', $request->all());
        }

        if ($request->has('text') || $request->has('data')) {
            return $this->qrService->apiResponse($request, 'text', $request->all());
        }

        $request->validate([
            'url' => ['required', 'url:http,https', 'max:2048'],
            'format' => ['nullable', Rule::in(self::FORMATS)],
        ]);

        $format = $request->input('format', 'svg');
        QrGeneration::create(['format' => $format]);

        return $this->render($request->input('url'), $format);
    }

    /**
     * GET /v1/qr/{type} — Type-specific API QR endpoint.
     */
    public function forType(Request $request, string $type): Response
    {
        $cleanType = strtolower(trim($type));

        if (! in_array($cleanType, QrCodeService::SUPPORTED_TYPES, true)) {
            abort(404, 'Unsupported QR code type');
        }

        return $this->qrService->apiResponse($request, $cleanType, $request->all());
    }

    /**
     * GET /qr/{url} — Pretty QR code, PNG by default (public hosts).
     * Accepts bare hostnames (example.com/…) as well as full URLs.
     */
    public function pretty(Request $request, string $url): Response
    {
        QrGeneration::create(['format' => 'png']);

        return $this->render($this->cleanUrl($url), 'png');
    }

    /**
     * GET /qr/{url}/{mime} — Pretty QR code in the requested format.
     */
    public function prettyMime(Request $request, string $url, string $mime): Response
    {
        QrGeneration::create(['format' => $mime]);

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

            QrGeneration::create(['link_id' => $link->id, 'format' => $mime]);

            return $this->render(LinkQrCode::shortUrl($link), $mime);
        }

        QrGeneration::create(['format' => $mime]);

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

        QrGeneration::create(['link_id' => $link->id, 'format' => $mime]);

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
