<?php

namespace App\Support;

/**
 * File-driven content collections (changelog, news, blog).
 *
 * Each collection is a directory of Markdown files:
 * resources/content/{collection}/{slug}.md with optional front matter:
 *
 *   ---
 *   title: Human Title
 *   date: 2026-09-26
 *   description: One-line summary for index pages.
 *   ---
 *
 *   Body…
 *
 * Files are auto-discovered — adding a post is adding a file, no
 * allowlist to maintain. Slugs come from filenames ([a-z0-9-]),
 * so traversal is impossible by construction; the controller
 * additionally confines reads to the collection directory.
 */
class ContentCollection
{
    /**
     * @var array<string, array{title: string, subtitle: string, item: string}> collection => meta
     */
    public const COLLECTIONS = [
        'changelog' => [
            'title' => 'Changelog',
            'subtitle' => 'Every shipped change to ternis.link, newest first.',
            'item' => 'Release',
        ],
        'news' => [
            'title' => 'News',
            'subtitle' => 'Announcements from the network.',
            'item' => 'Story',
        ],
        'blog' => [
            'title' => 'Blog',
            'subtitle' => 'Notes on building a private-by-design shortener.',
            'item' => 'Post',
        ],
    ];

    /**
     * All entries of a collection, newest first (stable by slug).
     *
     * @return list<array{slug: string, title: string, date: string, description: string}>
     */
    public static function entries(string $collection): array
    {
        $dir = self::directory($collection);

        if ($dir === null) {
            return [];
        }

        $entries = [];

        foreach ((array) glob($dir.'/*.md') as $path) {
            if (! is_file($path)) {
                continue;
            }

            $slug = basename($path, '.md');

            if (! preg_match('/^[a-z0-9-]+$/', $slug)) {
                continue;
            }

            $entries[] = self::parse($collection, $slug, (string) file_get_contents($path), (int) filemtime($path));
        }

        usort($entries, fn ($a, $b) => [$b['date'], $b['slug']] <=> [$a['date'], $a['slug']]);

        return $entries;
    }

    /**
     * A single entry incl. raw body, or null when missing/invalid.
     *
     * @return array{slug: string, title: string, date: string, description: string, body: string}|null
     */
    public static function entry(string $collection, string $slug): ?array
    {
        if (! preg_match('/^[a-z0-9-]+$/', $slug)) {
            return null;
        }

        $dir = self::directory($collection);

        if ($dir === null) {
            return null;
        }

        $path = $dir.'/'.$slug.'.md';

        // Confine reads to the collection dir even if a slug ever
        // slips past the regex (defense in depth, no traversal).
        $real = is_file($path) ? realpath($path) : false;

        if ($real === false || ! str_starts_with($real, realpath($dir).DIRECTORY_SEPARATOR)) {
            return null;
        }

        return self::parse($collection, $slug, (string) file_get_contents($real), (int) filemtime($real), true);
    }

    /**
     * Absolute path of the collection directory, or null for
     * unknown collections / missing directories.
     */
    public static function directory(string $collection): ?string
    {
        if (! isset(self::COLLECTIONS[$collection])) {
            return null;
        }

        $dir = resource_path("content/{$collection}");

        return is_dir($dir) ? $dir : null;
    }

    /**
     * @return array{slug: string, title: string, date: string, description: string, body?: string}
     */
    private static function parse(string $collection, string $slug, string $raw, int $mtime, bool $withBody = false): array
    {
        $meta = [];
        $body = $raw;

        if (str_starts_with($raw, "---\n")) {
            $end = strpos($raw, "\n---", 4);

            if ($end !== false) {
                foreach (explode("\n", substr($raw, 4, $end - 4)) as $line) {
                    if (str_contains($line, ':')) {
                        [$key, $value] = explode(':', $line, 2);
                        $meta[trim($key)] = trim($value, " \t\"'");
                    }
                }
                $body = ltrim(substr($raw, $end + 4));
            }
        }

        $date = self::parseDate($meta['date'] ?? null, $mtime);
        $title = $meta['title'] ?? null;
        $title = is_string($title) && $title !== '' ? $title : ucfirst(str_replace('-', ' ', $slug));

        $entry = [
            'slug' => $slug,
            'title' => $title,
            'date' => $date,
            'description' => self::description($meta['description'] ?? null, $body),
        ];

        if ($withBody) {
            $entry['body'] = $body;
        }

        return $entry;
    }

    private static function parseDate(mixed $value, int $mtime): string
    {
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            [$y, $m, $d] = array_map('intval', explode('-', $value));

            if (checkdate($m, $d, $y)) {
                return $value;
            }
        }

        return date('Y-m-d', $mtime);
    }

    private static function description(mixed $value, string $body): string
    {
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        foreach (explode("\n", $body) as $line) {
            $line = trim($line, " \t#>*-`");

            if ($line !== '') {
                return mb_strimwidth($line, 0, 160, '…');
            }
        }

        return '';
    }
}
