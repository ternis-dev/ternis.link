<?php

namespace App\Support;

/**
 * Privacy-friendly video embeds for bio pages. Only YouTube and Vimeo
 * are allowed, and nothing third-party loads until the visitor hits
 * play: the public page renders a facade (thumbnail + play button)
 * and swaps in the iframe on click.
 */
final class BioVideo
{
    /**
     * @return array{provider: string, id: string, embed: string}|null
     */
    public static function embed(string $url): ?array
    {
        $parts = parse_url(trim($url));

        if (! is_array($parts)) {
            return null;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $host = preg_replace('/^www\./', '', $host);
        $path = (string) ($parts['path'] ?? '');
        parse_str((string) ($parts['query'] ?? ''), $query);

        if ($host === 'youtube.com' && str_starts_with($path, '/watch') && ! empty($query['v'])) {
            $id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $query['v']);

            return $id !== '' ? [
                'provider' => 'youtube',
                'id' => $id,
                'embed' => "https://www.youtube-nocookie.com/embed/{$id}",
            ] : null;
        }

        if ($host === 'youtu.be' && preg_match('#^/([a-zA-Z0-9_-]{6,20})$#', $path, $m)) {
            return [
                'provider' => 'youtube',
                'id' => $m[1],
                'embed' => "https://www.youtube-nocookie.com/embed/{$m[1]}",
            ];
        }

        if (in_array($host, ['youtube.com'], true) && preg_match('#^/shorts/([a-zA-Z0-9_-]{6,20})$#', $path, $m)) {
            return [
                'provider' => 'youtube',
                'id' => $m[1],
                'embed' => "https://www.youtube-nocookie.com/embed/{$m[1]}",
            ];
        }

        if (in_array($host, ['vimeo.com', 'player.vimeo.com'], true) && preg_match('#^/(?:video/)?(\d{5,12})$#', $path, $m)) {
            return [
                'provider' => 'vimeo',
                'id' => $m[1],
                'embed' => "https://player.vimeo.com/video/{$m[1]}?dnt=1",
            ];
        }

        return null;
    }

    public static function isVideoUrl(string $url): bool
    {
        return self::embed($url) !== null;
    }
}
