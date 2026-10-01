<?php

namespace App\Services;

use App\Exceptions\UnsafeUrlException;
use Illuminate\Support\Facades\Http;

class SocialPreviewValidator
{
    public const TITLE_MAX = 120;

    public const DESCRIPTION_MAX = 300;

    public const IMAGE_URL_MAX = 2048;

    public const IMAGE_MAX_BYTES = 5242880; // 5MB

    public const ALLOWED_IMAGE_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    public function __construct(
        private UnsafeUrlValidator $unsafeUrls,
    ) {}

    /**
     * @return array{og_title: ?string, og_description: ?string, og_image_url: ?string}
     */
    public function normalize(?string $title, ?string $description, ?string $imageUrl): array
    {
        $title = $title !== null && trim($title) !== ''
            ? mb_substr(trim(strip_tags($title)), 0, self::TITLE_MAX)
            : null;

        $description = $description !== null && trim($description) !== ''
            ? mb_substr(trim(strip_tags($description)), 0, self::DESCRIPTION_MAX)
            : null;

        $imageUrl = $imageUrl !== null && trim($imageUrl) !== ''
            ? trim($imageUrl)
            : null;

        if ($imageUrl !== null) {
            $this->rejectIfBadImageUrl($imageUrl);
        }

        return [
            'og_title' => $title,
            'og_description' => $description,
            'og_image_url' => $imageUrl,
        ];
    }

    public function rejectIfBadImageUrl(string $url): void
    {
        if (strlen($url) > self::IMAGE_URL_MAX) {
            throw UnsafeUrlException::forUrl($url, 'og-image-too-long');
        }

        $parts = parse_url($url);

        if (! is_array($parts) || strtolower((string) ($parts['scheme'] ?? '')) !== 'https') {
            throw UnsafeUrlException::forUrl($url, 'og-image-scheme');
        }

        // Structural safety (no intranet literals, no credentials).
        $this->unsafeUrls->rejectIfUnsafe($url);
    }

    /**
     * Best-effort HEAD check: content-type + size. Returns null when
     * unreachable so callers can warn instead of reject.
     *
     * @return array{ok: bool, content_type: ?string, bytes: ?int, error: ?string}
     */
    public function probeImage(string $url): array
    {
        try {
            $response = Http::timeout(3)->withHeaders(['User-Agent' => 'ternislink-og-probe/1.0'])->head($url);

            if (! $response->successful()) {
                return ['ok' => false, 'content_type' => null, 'bytes' => null, 'error' => 'unreachable'];
            }

            $type = $response->header('Content-Type');
            $type = is_string($type) && $type !== '' ? strtolower(explode(';', $type)[0]) : null;
            $length = $response->header('Content-Length');
            $bytes = is_numeric($length) ? (int) $length : null;

            if ($type !== null && ! in_array($type, self::ALLOWED_IMAGE_TYPES, true)) {
                return ['ok' => false, 'content_type' => $type, 'bytes' => $bytes, 'error' => 'content-type'];
            }

            if ($bytes !== null && $bytes > self::IMAGE_MAX_BYTES) {
                return ['ok' => false, 'content_type' => $type, 'bytes' => $bytes, 'error' => 'too-large'];
            }

            return ['ok' => true, 'content_type' => $type, 'bytes' => $bytes, 'error' => null];
        } catch (\Throwable) {
            return ['ok' => false, 'content_type' => null, 'bytes' => null, 'error' => 'unreachable'];
        }
    }
}
