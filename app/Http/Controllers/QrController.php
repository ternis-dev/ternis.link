<?php

namespace App\Http\Controllers;

use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class QrController extends Controller
{
    public function __construct(
        private QrCodeService $qrService,
    ) {}

    /**
     * GET / on qr.href.nz — Dedicated interactive QR code generator landing page.
     */
    public function landing(Request $request): Response
    {
        return response()->view('landing.qr');
    }

    /**
     * GET /url/{url} — QR code encoding an arbitrary URL or short link.
     */
    public function generateUrl(Request $request, string $url): Response
    {
        [$cleanUrl, $format] = $this->resolveFormatAndValue($request, $url);

        $payload = $this->qrService->buildPayload('url', ['url' => $cleanUrl]);

        return $this->respond($request, $payload, $format, 'qr-url');
    }

    /**
     * GET /text/{text} — QR code encoding plain text.
     */
    public function generateText(Request $request, string $text): Response
    {
        [$cleanText, $format] = $this->resolveFormatAndValue($request, $text);

        $payload = $this->qrService->buildPayload('text', ['text' => $cleanText]);

        return $this->respond($request, $payload, $format, 'qr-text');
    }

    /**
     * GET /wifi/{ssid?} — QR code for connecting to a Wi-Fi network.
     */
    public function generateWifi(Request $request, ?string $ssid = null): Response
    {
        $ssidValue = $ssid ?? (string) $request->query('ssid', $request->query('name', ''));
        [$cleanSsid, $format] = $this->resolveFormatAndValue($request, $ssidValue);

        $payload = $this->qrService->buildPayload('wifi', [
            'ssid' => $cleanSsid,
            'password' => $request->query('password', $request->query('pass', '')),
            'encryption' => $request->query('encryption', $request->query('type', 'WPA')),
            'hidden' => $request->boolean('hidden'),
        ]);

        return $this->respond($request, $payload, $format, 'qr-wifi');
    }

    /**
     * GET /vcard, /contact — QR code for a digital business card (vCard 3.0).
     */
    public function generateVcard(Request $request): Response
    {
        $format = $this->resolveFormat($request);
        $payload = $this->qrService->buildPayload('vcard', $request->all());

        return $this->respond($request, $payload, $format, 'qr-contact');
    }

    /**
     * GET /email/{email?} — QR code for composing an email (mailto:).
     */
    public function generateEmail(Request $request, ?string $email = null): Response
    {
        $emailVal = $email ?? (string) $request->query('email', $request->query('to', ''));
        [$cleanEmail, $format] = $this->resolveFormatAndValue($request, $emailVal);

        $payload = $this->qrService->buildPayload('email', [
            'email' => $cleanEmail,
            'subject' => $request->query('subject', ''),
            'body' => $request->query('body', $request->query('message', '')),
        ]);

        return $this->respond($request, $payload, $format, 'qr-email');
    }

    /**
     * GET /phone/{phone}, /tel/{phone} — QR code for initiating a phone call.
     */
    public function generatePhone(Request $request, string $phone): Response
    {
        [$cleanPhone, $format] = $this->resolveFormatAndValue($request, $phone);
        $payload = $this->qrService->buildPayload('phone', ['phone' => $cleanPhone]);

        return $this->respond($request, $payload, $format, 'qr-phone');
    }

    /**
     * GET /sms/{phone?} — QR code for sending an SMS.
     */
    public function generateSms(Request $request, ?string $phone = null): Response
    {
        $phoneVal = $phone ?? (string) $request->query('phone', $request->query('number', ''));
        [$cleanPhone, $format] = $this->resolveFormatAndValue($request, $phoneVal);

        $payload = $this->qrService->buildPayload('sms', [
            'phone' => $cleanPhone,
            'message' => $request->query('message', $request->query('body', '')),
        ]);

        return $this->respond($request, $payload, $format, 'qr-sms');
    }

    /**
     * GET /whatsapp/{phone?} — QR code for opening a WhatsApp chat.
     */
    public function generateWhatsapp(Request $request, ?string $phone = null): Response
    {
        $phoneVal = $phone ?? (string) $request->query('phone', $request->query('number', ''));
        [$cleanPhone, $format] = $this->resolveFormatAndValue($request, $phoneVal);

        $payload = $this->qrService->buildPayload('whatsapp', [
            'phone' => $cleanPhone,
            'message' => $request->query('message', $request->query('text', '')),
        ]);

        return $this->respond($request, $payload, $format, 'qr-whatsapp');
    }

    /**
     * GET /geo/{coords?} — QR code for GPS coordinates.
     */
    public function generateGeo(Request $request, ?string $coords = null): Response
    {
        $coordsVal = $coords ?? (string) $request->query('coords', '');
        [$cleanCoords, $format] = $this->resolveFormatAndValue($request, $coordsVal);

        $payload = $this->qrService->buildPayload('geo', [
            'coords' => $cleanCoords,
            'latitude' => $request->query('lat', $request->query('latitude', '')),
            'longitude' => $request->query('lng', $request->query('longitude', '')),
            'label' => $request->query('label', $request->query('name', '')),
        ]);

        return $this->respond($request, $payload, $format, 'qr-geo');
    }

    /**
     * GET /event, /calendar — QR code for a calendar event (iCal VEVENT).
     */
    public function generateEvent(Request $request): Response
    {
        $format = $this->resolveFormat($request);
        $payload = $this->qrService->buildPayload('event', $request->all());

        return $this->respond($request, $payload, $format, 'qr-event');
    }

    /**
     * GET /crypto/{address?} — QR code for a cryptocurrency payment address.
     */
    public function generateCrypto(Request $request, ?string $address = null): Response
    {
        $addrVal = $address ?? (string) $request->query('address', $request->query('wallet', ''));
        [$cleanAddr, $format] = $this->resolveFormatAndValue($request, $addrVal);

        $payload = $this->qrService->buildPayload('crypto', [
            'address' => $cleanAddr,
            'currency' => $request->query('currency', $request->query('coin', 'bitcoin')),
            'amount' => $request->query('amount', ''),
        ]);

        return $this->respond($request, $payload, $format, 'qr-crypto');
    }

    /**
     * GET /raw/{data} — QR code encoding raw input string verbatim.
     */
    public function generateRaw(Request $request, string $data): Response
    {
        [$cleanData, $format] = $this->resolveFormatAndValue($request, $data);
        $payload = $this->qrService->buildPayload('raw', ['data' => $cleanData]);

        return $this->respond($request, $payload, $format, 'qr-raw');
    }

    /**
     * Unified API handler for GET and POST /v1/qr[/{type}].
     */
    public function api(Request $request, ?string $type = null): Response
    {
        $resolvedType = $type ?? (string) $request->input('type', $request->query('type', 'url'));

        if (! in_array(strtolower($resolvedType), QrCodeService::SUPPORTED_TYPES, true)) {
            $resolvedType = 'url';
        }

        $allData = $request->isMethod('post') ? $request->all() : $request->query();

        return $this->qrService->apiResponse($request, $resolvedType, $allData);
    }

    private function resolveFormat(Request $request, string $default = 'svg'): string
    {
        $fmt = strtolower((string) $request->query('format', $default));

        return in_array($fmt, ['svg', 'png', 'json'], true) ? $fmt : $default;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveFormatAndValue(Request $request, string $value, string $default = 'svg'): array
    {
        $queryFormat = $request->query('format');
        if ($queryFormat && in_array(strtolower((string) $queryFormat), ['svg', 'png', 'json'], true)) {
            return [trim($value), strtolower((string) $queryFormat)];
        }

        if (preg_match('/\.(svg|png)$/i', $value, $matches)) {
            $clean = substr($value, 0, -strlen($matches[0]));

            return [trim($clean), strtolower($matches[1])];
        }

        return [trim($value), $default];
    }

    private function respond(Request $request, string $payload, string $format, string $filename): Response
    {
        $size = (int) $request->query('size', 300);
        $margin = (int) $request->query('margin', 10);
        $fg = $request->query('color', $request->query('fg'));
        $bg = $request->query('bg', $request->query('background'));
        $level = $request->query('error_correction', $request->query('level', 'M'));
        $download = $request->boolean('download');

        if ($format === 'json' || $request->wantsJson()) {
            $type = str_starts_with($filename, 'qr-') ? substr($filename, 3) : $filename;

            return $this->qrService->apiResponse($request, $type, [
                'data' => $payload,
                'format' => 'json',
                'size' => $size,
                'margin' => $margin,
                'color' => $fg,
                'bg' => $bg,
                'error_correction' => $level,
            ]);
        }

        return $this->qrService->renderResponse(
            payload: $payload,
            format: $format,
            size: $size,
            margin: $margin,
            foregroundColor: $fg,
            backgroundColor: $bg,
            errorCorrection: $level,
            download: $download,
            filename: $filename
        );
    }
}
