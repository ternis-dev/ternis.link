<?php

namespace App\Services;

use App\Models\QrGeneration;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class QrCodeService
{
    public const SUPPORTED_TYPES = [
        'url',
        'text',
        'wifi',
        'vcard',
        'contact',
        'email',
        'phone',
        'tel',
        'sms',
        'whatsapp',
        'geo',
        'event',
        'calendar',
        'crypto',
        'raw',
    ];

    public const SUPPORTED_FORMATS = ['svg', 'png'];

    /**
     * Build the raw QR code string payload for any supported QR type.
     *
     * @param  array<string, mixed>|string  $input
     */
    public function buildPayload(string $type, array|string $input): string
    {
        $type = strtolower(trim($type));

        if (is_string($input)) {
            $data = ['data' => $input, 'text' => $input, 'url' => $input, 'value' => $input];
        } else {
            $data = $input;
        }

        return match ($type) {
            'url' => $this->buildUrlPayload($data),
            'text' => (string) ($data['text'] ?? $data['data'] ?? $data['value'] ?? ''),
            'wifi' => $this->buildWifiPayload($data),
            'vcard', 'contact' => $this->buildVcardPayload($data),
            'email' => $this->buildEmailPayload($data),
            'phone', 'tel' => $this->buildPhonePayload($data),
            'sms' => $this->buildSmsPayload($data),
            'whatsapp' => $this->buildWhatsappPayload($data),
            'geo' => $this->buildGeoPayload($data),
            'event', 'calendar' => $this->buildEventPayload($data),
            'crypto' => $this->buildCryptoPayload($data),
            default => (string) ($data['data'] ?? $data['text'] ?? $data['value'] ?? ''),
        };
    }

    /**
     * Render the QR code image string (SVG or PNG).
     */
    public function renderString(
        string $payload,
        string $format = 'svg',
        int $size = 300,
        int $margin = 10,
        ?string $foregroundColor = null,
        ?string $backgroundColor = null,
        ?string $errorCorrection = null
    ): string {
        $format = strtolower($format) === 'png' ? 'png' : 'svg';
        $size = max(64, min(2048, $size));
        $margin = max(0, min(100, $margin));

        $fg = $foregroundColor ? $this->parseHexColor($foregroundColor, new Color(0, 0, 0)) : new Color(0, 0, 0);
        $bg = $backgroundColor ? $this->parseHexColor($backgroundColor, new Color(255, 255, 255)) : new Color(255, 255, 255);
        $level = $this->parseErrorCorrectionLevel($errorCorrection);

        $qr = new QrCode(
            data: $payload,
            errorCorrectionLevel: $level,
            size: $size,
            margin: $margin,
            foregroundColor: $fg,
            backgroundColor: $bg
        );

        return $format === 'png'
            ? (new PngWriter)->write($qr)->getString()
            : (new SvgWriter)->write($qr)->getString();
    }

    /**
     * Generate a base64 Data URI for inline display.
     */
    public function renderDataUri(
        string $payload,
        string $format = 'svg',
        int $size = 300,
        int $margin = 10,
        ?string $foregroundColor = null,
        ?string $backgroundColor = null,
        ?string $errorCorrection = null
    ): string {
        $format = strtolower($format) === 'png' ? 'png' : 'svg';
        $bytes = $this->renderString(
            payload: $payload,
            format: $format,
            size: $size,
            margin: $margin,
            foregroundColor: $foregroundColor,
            backgroundColor: $backgroundColor,
            errorCorrection: $errorCorrection
        );

        if ($format === 'png') {
            return 'data:image/png;base64,'.base64_encode($bytes);
        }

        return 'data:image/svg+xml;utf8,'.rawurlencode($bytes);
    }

    /**
     * Build an HTTP Response delivering the QR code image.
     */
    public function renderResponse(
        string $payload,
        string $format = 'svg',
        int $size = 300,
        int $margin = 10,
        ?string $foregroundColor = null,
        ?string $backgroundColor = null,
        ?string $errorCorrection = null,
        bool $download = false,
        string $filename = 'qr-code'
    ): Response {
        $format = strtolower($format) === 'png' ? 'png' : 'svg';
        $content = $this->renderString(
            payload: $payload,
            format: $format,
            size: $size,
            margin: $margin,
            foregroundColor: $foregroundColor,
            backgroundColor: $backgroundColor,
            errorCorrection: $errorCorrection
        );

        QrGeneration::create(['format' => $format]);

        $contentType = $format === 'png' ? 'image/png' : 'image/svg+xml';
        $disposition = $download ? 'attachment' : 'inline';

        return response($content, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => "{$disposition}; filename=\"{$filename}.{$format}\"",
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * Build a structured API response (JSON or direct image based on format / Accept header).
     */
    public function apiResponse(Request $request, string $type, array|string $data): Response
    {
        $payload = $this->buildPayload($type, $data);

        $format = strtolower((string) ($request->input('format', $request->query('format', 'svg'))));
        $size = (int) $request->input('size', $request->query('size', 300));
        $margin = (int) $request->input('margin', $request->query('margin', 10));
        $fg = $request->input('color', $request->query('color', $request->input('foreground_color', $request->query('foreground_color'))));
        $bg = $request->input('bg', $request->query('bg', $request->input('background_color', $request->query('background_color'))));
        $level = $request->input('error_correction', $request->query('error_correction', $request->input('level', $request->query('level', 'M'))));
        $download = $request->boolean('download');

        if ($format === 'json' || $request->wantsJson()) {
            QrGeneration::create(['format' => 'json']);

            $dataUri = $this->renderDataUri(
                payload: $payload,
                format: 'svg',
                size: $size,
                margin: $margin,
                foregroundColor: $fg,
                backgroundColor: $bg,
                errorCorrection: $level
            );

            $pngDataUri = $this->renderDataUri(
                payload: $payload,
                format: 'png',
                size: $size,
                margin: $margin,
                foregroundColor: $fg,
                backgroundColor: $bg,
                errorCorrection: $level
            );

            return response()->json([
                'success' => true,
                'type' => $type,
                'payload' => $payload,
                'options' => [
                    'size' => $size,
                    'margin' => $margin,
                    'color' => $fg,
                    'background' => $bg,
                    'error_correction' => $this->parseErrorCorrectionLevel($level)->value,
                ],
                'data_uri' => $dataUri,
                'png_data_uri' => $pngDataUri,
                'download_url' => url()->current().'?'.http_build_query(array_merge($request->query(), ['download' => 1])),
            ]);
        }

        return $this->renderResponse(
            payload: $payload,
            format: $format,
            size: $size,
            margin: $margin,
            foregroundColor: $fg,
            backgroundColor: $bg,
            errorCorrection: $level,
            download: $download,
            filename: 'qr-'.$type
        );
    }

    /**
     * Parse hex color string to Endroid Color object.
     */
    public function parseHexColor(string $hex, Color $default): Color
    {
        $clean = ltrim(trim($hex), '#');

        if (strlen($clean) === 3) {
            $clean = $clean[0].$clean[0].$clean[1].$clean[1].$clean[2].$clean[2];
        }

        if (strlen($clean) === 6 && ctype_xdigit($clean)) {
            return new Color(
                hexdec(substr($clean, 0, 2)),
                hexdec(substr($clean, 2, 2)),
                hexdec(substr($clean, 4, 2))
            );
        }

        return $default;
    }

    /**
     * Parse error correction level.
     */
    public function parseErrorCorrectionLevel(?string $level): ErrorCorrectionLevel
    {
        return match (strtolower(trim((string) $level))) {
            'l', 'low', '7%' => ErrorCorrectionLevel::Low,
            'q', 'quartile', '25%' => ErrorCorrectionLevel::Quartile,
            'h', 'high', '30%' => ErrorCorrectionLevel::High,
            default => ErrorCorrectionLevel::Medium,
        };
    }

    private function buildUrlPayload(array $data): string
    {
        $url = trim((string) ($data['url'] ?? $data['data'] ?? $data['value'] ?? ''));

        if ($url === '') {
            return 'https://href.nz';
        }

        // If no scheme provided and looks like a hostname/path, prepend https://
        if (! preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*://#', $url)) {
            $url = 'https://'.$url;
        }

        return $url;
    }

    private function buildWifiPayload(array $data): string
    {
        $ssid = (string) ($data['ssid'] ?? $data['name'] ?? $data['value'] ?? '');
        $password = (string) ($data['password'] ?? $data['pass'] ?? '');
        $encryption = strtoupper((string) ($data['encryption'] ?? $data['type'] ?? 'WPA'));
        $hidden = ! empty($data['hidden']) ? 'true' : 'false';

        if (! in_array($encryption, ['WPA', 'WEP', 'nopass'], true)) {
            $encryption = 'WPA';
        }

        $escape = fn (string $s) => addcslashes($s, '\\;,":');

        return sprintf(
            'WIFI:T:%s;S:%s;P:%s;H:%s;;',
            $encryption,
            $escape($ssid),
            $escape($password),
            $hidden
        );
    }

    private function buildVcardPayload(array $data): string
    {
        $firstName = trim((string) ($data['first_name'] ?? $data['firstName'] ?? ''));
        $lastName = trim((string) ($data['last_name'] ?? $data['lastName'] ?? ''));
        $fullName = trim((string) ($data['name'] ?? $data['full_name'] ?? "{$firstName} {$lastName}"));

        $org = trim((string) ($data['org'] ?? $data['company'] ?? ''));
        $title = trim((string) ($data['title'] ?? $data['role'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? $data['tel'] ?? $data['mobile'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $url = trim((string) ($data['url'] ?? $data['website'] ?? ''));
        $note = trim((string) ($data['note'] ?? $data['bio'] ?? ''));

        $lines = [
            'BEGIN:VCARD',
            'VERSION:3.0',
            "N:{$lastName};{$firstName};;;",
            "FN:{$fullName}",
        ];

        if ($org !== '') {
            $lines[] = "ORG:{$org}";
        }
        if ($title !== '') {
            $lines[] = "TITLE:{$title}";
        }
        if ($phone !== '') {
            $lines[] = "TEL;TYPE=CELL:{$phone}";
        }
        if ($email !== '') {
            $lines[] = "EMAIL;TYPE=INTERNET:{$email}";
        }
        if ($url !== '') {
            $lines[] = "URL:{$url}";
        }
        if ($note !== '') {
            $lines[] = "NOTE:{$note}";
        }

        $lines[] = 'END:VCARD';

        return implode("\n", $lines);
    }

    private function buildEmailPayload(array $data): string
    {
        $to = trim((string) ($data['email'] ?? $data['to'] ?? $data['value'] ?? ''));
        $subject = trim((string) ($data['subject'] ?? ''));
        $body = trim((string) ($data['body'] ?? $data['message'] ?? ''));

        $query = [];
        if ($subject !== '') {
            $query['subject'] = $subject;
        }
        if ($body !== '') {
            $query['body'] = $body;
        }

        $qs = $query !== [] ? '?'.http_build_query($query) : '';

        return "mailto:{$to}{$qs}";
    }

    private function buildPhonePayload(array $data): string
    {
        $phone = trim((string) ($data['phone'] ?? $data['number'] ?? $data['tel'] ?? $data['value'] ?? ''));
        $clean = preg_replace('/[^\d+]/', '', $phone);

        return "tel:{$clean}";
    }

    private function buildSmsPayload(array $data): string
    {
        $phone = trim((string) ($data['phone'] ?? $data['number'] ?? $data['to'] ?? $data['value'] ?? ''));
        $cleanPhone = preg_replace('/[^\d+]/', '', $phone);
        $message = trim((string) ($data['message'] ?? $data['body'] ?? $data['text'] ?? ''));

        return "smsto:{$cleanPhone}:{$message}";
    }

    private function buildWhatsappPayload(array $data): string
    {
        $phone = trim((string) ($data['phone'] ?? $data['number'] ?? $data['value'] ?? ''));
        $cleanPhone = ltrim(preg_replace('/[^\d]/', '', $phone), '+');
        $message = trim((string) ($data['message'] ?? $data['text'] ?? ''));

        $qs = $message !== '' ? '?text='.rawurlencode($message) : '';

        return "https://wa.me/{$cleanPhone}{$qs}";
    }

    private function buildGeoPayload(array $data): string
    {
        $lat = (string) ($data['latitude'] ?? $data['lat'] ?? '');
        $lng = (string) ($data['longitude'] ?? $data['lng'] ?? $data['lon'] ?? '');

        if ($lat === '' && isset($data['coords'])) {
            $parts = explode(',', (string) $data['coords']);
            $lat = trim($parts[0] ?? '');
            $lng = trim($parts[1] ?? '');
        }

        $label = trim((string) ($data['label'] ?? $data['name'] ?? ''));

        if ($label !== '') {
            return "geo:{$lat},{$lng}?q={$lat},{$lng}(".rawurlencode($label).')';
        }

        return "geo:{$lat},{$lng}";
    }

    private function buildEventPayload(array $data): string
    {
        $title = trim((string) ($data['title'] ?? $data['summary'] ?? $data['name'] ?? 'Event'));
        $description = trim((string) ($data['description'] ?? $data['desc'] ?? ''));
        $location = trim((string) ($data['location'] ?? ''));

        $formatDate = function ($val, string $fallback): string {
            if (! $val) {
                return $fallback;
            }
            try {
                $dt = new \DateTimeImmutable((string) $val);

                return $dt->setTimezone(new \DateTimeZone('UTC'))->format('Ymd\THis\Z');
            } catch (\Throwable) {
                return $fallback;
            }
        };

        $nowStr = gmdate('Ymd\THis\Z');
        $startStr = $formatDate($data['start'] ?? $data['starts_at'] ?? null, $nowStr);
        $endStr = $formatDate($data['end'] ?? $data['ends_at'] ?? null, gmdate('Ymd\THis\Z', strtotime('+1 hour')));

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//ternis.link//qr.href.nz//EN',
            'BEGIN:VEVENT',
            "SUMMARY:{$title}",
            "DTSTART:{$startStr}",
            "DTEND:{$endStr}",
        ];

        if ($location !== '') {
            $lines[] = "LOCATION:{$location}";
        }
        if ($description !== '') {
            $lines[] = "DESCRIPTION:{$description}";
        }

        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        return implode("\n", $lines);
    }

    private function buildCryptoPayload(array $data): string
    {
        $currency = strtolower(trim((string) ($data['currency'] ?? $data['coin'] ?? 'bitcoin')));
        $address = trim((string) ($data['address'] ?? $data['wallet'] ?? $data['value'] ?? ''));
        $amount = trim((string) ($data['amount'] ?? ''));

        $scheme = match ($currency) {
            'btc', 'bitcoin' => 'bitcoin',
            'eth', 'ethereum' => 'ethereum',
            'sol', 'solana' => 'solana',
            'ltc', 'litecoin' => 'litecoin',
            'doge', 'dogecoin' => 'dogecoin',
            default => $currency,
        };

        $qs = $amount !== '' ? "?amount={$amount}" : '';

        return "{$scheme}:{$address}{$qs}";
    }
}
