<?php

namespace App\Http\Controllers;

use App\Services\AiThreatDetectionService;
use App\Support\DomainUrls;
use App\Support\PublicHost;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ScanUiController extends Controller
{
    public function __construct(
        protected AiThreatDetectionService $threatService,
    ) {}

    public function show(Request $request)
    {
        $host = $request->getHost();
        $type = $request->attributes->get('domain_type');

        $isHrefNz = $host === (string) config('domains.public_host', 'href.nz')
            || (! PublicHost::isMeinlink($host) && ! PublicHost::isClicked($host) && ! PublicHost::isQr($host) && ! PublicHost::isYt($host) && $type === 'public');
        $isMeinlink = PublicHost::isMeinlink($host);
        $isTernis = $type === 'ternis' && ! DomainUrls::isInternal();

        if (! ($isHrefNz || $isMeinlink || $isTernis)) {
            throw new NotFoundHttpException('Link scanning UI is not available on this domain.');
        }

        $locale = $isMeinlink ? 'de' : 'en';
        $domain = $isMeinlink ? 'meinlink.at' : ($isHrefNz ? 'href.nz' : 'ternis.link');

        $url = $request->input('url') ?? $request->query('url');
        $result = null;

        if (is_string($url) && trim($url) !== '') {
            $result = $this->threatService->scanUrl(trim($url));
        }

        if ($request->wantsJson()) {
            return response()->json([
                'data' => $result,
            ]);
        }

        return view('pages.scan', [
            'locale' => $locale,
            'domain' => $domain,
            'url' => is_string($url) ? trim($url) : '',
            'result' => $result,
        ]);
    }
}
