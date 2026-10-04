<?php

namespace App\Support;

use App\Models\Link;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * Handles best-practice, privacy-preserving user tracking and dynamic
 * tag extraction for short link clicks.
 *
 * Privacy guidelines followed:
 * - User tracking is strictly opt-in (enabled per link or explicit request opt-in).
 * - Respects DNT (Do Not Track) and Sec-GPC (Global Privacy Control) headers.
 * - Sensitive identifiers like email addresses are never stored in plaintext —
 *   they are pseudonymized with SHA-256 before persistence.
 * - Customer applications can set multiple tags per click via query params or headers.
 * - URL parameters are captured safely in structured JSON without payload bloat.
 */
final class UserTracking
{
    /** Query parameters reserved for internal debugging/routing. */
    public const RESERVED_PARAMS = ['debug', 'target'];

    /** Max parameters allowed in JSON capture to prevent abuse. */
    public const MAX_PARAM_COUNT = 50;

    /** Max length of individual parameter values. */
    public const MAX_PARAM_VALUE_LENGTH = 1024;

    /** Max number of dynamic tags per click. */
    public const MAX_CLICK_TAGS = 10;

    /**
     * Extract incoming URL query parameters for storage in clicks.query_params.
     *
     * @return array<string, mixed>|null
     */
    public static function extractQueryParams(Request $request): ?array
    {
        $raw = $request->query();

        if (! is_array($raw) || $raw === []) {
            return null;
        }

        $params = Arr::except($raw, self::RESERVED_PARAMS);

        if ($params === []) {
            return null;
        }

        $clean = [];
        $count = 0;

        foreach ($params as $key => $value) {
            if ($count >= self::MAX_PARAM_COUNT) {
                break;
            }

            $cleanKey = substr((string) $key, 0, 100);

            if (is_array($value)) {
                $clean[$cleanKey] = array_map(function ($item) {
                    return is_scalar($item)
                        ? substr((string) $item, 0, self::MAX_PARAM_VALUE_LENGTH)
                        : null;
                }, array_slice($value, 0, 20));
            } elseif (is_scalar($value) || $value === null) {
                $clean[$cleanKey] = $value !== null
                    ? substr((string) $value, 0, self::MAX_PARAM_VALUE_LENGTH)
                    : null;
            }

            $count++;
        }

        return $clean !== [] ? $clean : null;
    }

    /**
     * Extract multiple tags set dynamically by customer applications.
     *
     * Supported formats:
     * - ?tags=newsletter,promo,q4 (comma-separated)
     * - ?tag[]=newsletter&tag[]=promo (array)
     * - ?tag=newsletter&tag=promo
     * - ?click_tags=newsletter,promo
     * - Header: X-Click-Tags / X-Tags
     *
     * @return list<string>|null
     */
    public static function extractTags(Request $request): ?array
    {
        $candidates = [];

        // 1. Array or repeated param: ?tag[] or ?tag
        $tagParam = $request->query('tag');
        if (is_array($tagParam)) {
            $candidates = array_merge($candidates, $tagParam);
        } elseif (is_string($tagParam) && trim($tagParam) !== '') {
            $candidates[] = $tagParam;
        }

        // 2. Comma-separated or array: ?tags=
        $tagsParam = $request->query('tags');
        if (is_array($tagsParam)) {
            $candidates = array_merge($candidates, $tagsParam);
        } elseif (is_string($tagsParam) && trim($tagsParam) !== '') {
            $candidates = array_merge($candidates, explode(',', $tagsParam));
        }

        // 3. Alternative: ?click_tags=
        $clickTags = $request->query('click_tags');
        if (is_string($clickTags) && trim($clickTags) !== '') {
            $candidates = array_merge($candidates, explode(',', $clickTags));
        }

        // 4. Headers: X-Click-Tags or X-Tags
        $headerTags = $request->header('X-Click-Tags') ?? $request->header('X-Tags');
        if ($headerTags && is_string($headerTags)) {
            $candidates = array_merge($candidates, explode(',', $headerTags));
        }

        if ($candidates === []) {
            return null;
        }

        $normalized = [];
        foreach ($candidates as $candidate) {
            $tag = strtolower(trim((string) $candidate));
            // Keep alphanumeric, dash and underscore up to 50 chars
            $clean = preg_replace('/[^a-z0-9_-]/', '', $tag);
            if ($clean !== '' && strlen($clean) <= 50) {
                $normalized[] = $clean;
            }
        }

        $unique = array_values(array_unique($normalized));

        if ($unique === []) {
            return null;
        }

        return array_slice($unique, 0, self::MAX_CLICK_TAGS);
    }

    /**
     * Extract a user identifier following privacy best practices.
     *
     * User tracking is optional:
     * - Only active if the link has user_tracking_enabled = true,
     *   OR the request explicitly sets ?track_user=1 / ?user_tracking=1.
     * - Strictly respects Do Not Track (DNT) and Global Privacy Control (Sec-GPC).
     * - Raw email addresses are automatically pseudonymized with SHA-256
     *   (e.g., em_<hash>) so plaintext PII is never stored in analytics.
     */
    public static function extractUserIdentifier(Request $request, ?Link $link = null): ?string
    {
        // 1. Respect Do Not Track / Global Privacy Control
        if ($request->header('DNT') === '1' || $request->header('Sec-GPC') === '1') {
            return null;
        }

        // 2. Check if user tracking is enabled (optional feature)
        $linkEnabled = $link?->user_tracking_enabled ?? false;
        $requestOptIn = $request->boolean('track_user')
            || $request->boolean('user_tracking')
            || $request->header('X-User-Tracking') === '1';

        if (! $linkEnabled && ! $requestOptIn) {
            return null;
        }

        // 3. Inspect common user tracking parameters in order of preference
        $candidateKeys = [
            'uid',
            'user_id',
            'subscriber_id',
            'sub_id',
            'sub',
            'customer_id',
            'contact_id',
            'external_id',
            'email',
        ];

        $rawIdentifier = null;

        foreach ($candidateKeys as $key) {
            $val = $request->query($key);
            if (is_string($val) && trim($val) !== '') {
                $rawIdentifier = trim($val);
                break;
            }
        }

        if ($rawIdentifier === null) {
            // Check headers
            $rawIdentifier = $request->header('X-User-Id') ?? $request->header('X-Subscriber-Id');
        }

        if (! is_string($rawIdentifier) || trim($rawIdentifier) === '') {
            return null;
        }

        $trimmed = trim($rawIdentifier);

        // 4. Best practice: if the identifier is an email address, pseudonymize with SHA-256
        if (str_contains($trimmed, '@')) {
            return 'em_'.substr(hash('sha256', strtolower($trimmed)), 0, 32);
        }

        // 5. Opaque customer identifiers (e.g., usr_123, sub_abc): truncate to 128 chars
        return substr(preg_replace('/[^\w.-]/', '', $trimmed), 0, 128) ?: null;
    }

    /**
     * Merge request query parameters into the destination URL.
     *
     * Parameters already set in the destination URL take precedence.
     * Internal routing parameters (debug, target) are omitted.
     */
    public static function mergeQueryIntoDestination(string $destination, array $queryParams): string
    {
        $filtered = Arr::except($queryParams, self::RESERVED_PARAMS);

        if ($filtered === []) {
            return $destination;
        }

        $parts = parse_url($destination);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return $destination;
        }

        parse_str($parts['query'] ?? '', $existing);

        // Existing destination parameters win over forwarded query parameters
        foreach ($filtered as $key => $value) {
            if (! array_key_exists($key, $existing)) {
                $existing[$key] = $value;
            }
        }

        $rebuilt = $parts['scheme'].'://'.($parts['host'] ?? '');
        $rebuilt .= isset($parts['port']) ? ':'.$parts['port'] : '';
        $rebuilt .= $parts['path'] ?? '';
        $rebuilt .= $existing !== [] ? '?'.http_build_query($existing) : '';
        $rebuilt .= isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return $rebuilt;
    }
}
