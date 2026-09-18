<?php

namespace App\Services;

class SlugResolverService
{
    /**
     * Determine if the input is a URL (should direct-redirect) or a slug (should look up).
     *
     * Rules:
     * - Contains a dot (.), colon (:), or forward slash (/) → URL
     * - Matches slug charset [a-zA-Z0-9_-] only → slug
     *
     * @return 'url'|'slug'
     */
    public function classify(string $input): string
    {
        // If it contains dot, colon, or slash → it's a URL
        if (preg_match('/[.:\/]/', $input)) {
            return 'url';
        }

        // If it only contains valid slug characters → it's a slug
        if (preg_match('/^[a-zA-Z0-9_-]+$/', $input)) {
            return 'slug';
        }

        // Fallback: treat as URL (handles edge cases like encoded chars)
        return 'url';
    }

    /**
     * Normalize a URL for redirect (ensure it has a scheme).
     */
    public function normalizeUrl(string $url): string
    {
        // If no scheme, prepend https://
        if (! preg_match('#^https?://#i', $url)) {
            return 'https://'.$url;
        }

        return $url;
    }
}
