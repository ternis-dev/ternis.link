<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\LinkQrCode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class PublicQrCodeController extends Controller
{
    /**
     * GET /v1/qr — Generate a QR code for an arbitrary public URL.
     *
     * SVG is the default format; PNG can be requested with ?format=png.
     */
    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'url' => ['required', 'url:http,https', 'max:2048'],
            'format' => ['nullable', Rule::in(['svg', 'png'])],
        ]);

        $url = $validated['url'];
        $format = $validated['format'] ?? 'svg';
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
