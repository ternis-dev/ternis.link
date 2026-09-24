<?php

namespace App\Services;

use App\Models\Link;

class SlugGeneratorService
{
    /**
     * Valid slug characters: [a-zA-Z0-9_-]
     * No dots, colons, or slashes — this enables deterministic URL vs slug detection.
     */
    private const CHARSET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-';

    /**
     * Generate a unique slug for the given domain.
     *
     * @param  int  $length  Slug length (determined by user's plan min_slug_length)
     * @param  string  $domainId  Domain to check uniqueness against
     */
    public function generate(int $length, string $domainId): string
    {
        $maxAttempts = 10;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $slug = $this->randomSlug($length);

            // Check uniqueness within this domain
            $exists = Link::where('domain_id', $domainId)
                ->where('slug', $slug)
                ->exists();

            if (! $exists) {
                return $slug;
            }
        }

        // If all attempts collide, increase length by 1 and try again
        return $this->generate($length + 1, $domainId);
    }

    private function randomSlug(int $length): string
    {
        $slug = '';
        $charsetLength = strlen(self::CHARSET);

        for ($i = 0; $i < $length; $i++) {
            $slug .= self::CHARSET[random_int(0, $charsetLength - 1)];
        }

        return $slug;
    }
}
