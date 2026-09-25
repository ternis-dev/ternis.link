<?php

namespace App\Support;

/**
 * Short Git commit id stamped on built asset URLs (`?v=abc1234`).
 *
 * Lets a served stylesheet be traced back to the commit it was built
 * from and busts caches as soon as a new build ships. Resolution order:
 * `APP_COMMIT` config override → `.git` HEAD → no stamp at all.
 */
final class CommitVersion
{
    /**
     * Whether the value has been resolved for this process.
     */
    private static bool $resolved = false;

    /**
     * Whether `pin()` fixed the value instead of resolving it.
     */
    private static bool $pinned = false;

    /**
     * Resolved (or pinned) short commit id; null = no stamp.
     */
    private static ?string $short = null;

    /**
     * Short commit id (7 hex characters), or null when it is unknown.
     */
    public static function short(): ?string
    {
        if (! self::$pinned && ! self::$resolved) {
            self::$resolved = true;
            self::$short = self::normalize((string) config('app.asset_commit'))
                ?? self::fromGit();
        }

        return self::$short;
    }

    /**
     * Append the short commit id to an asset URL (?v=… or &v=…).
     */
    public static function forUrl(string $url): string
    {
        $short = self::short();

        if ($short === null) {
            return $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?').'v='.$short;
    }

    /**
     * Fix the value instead of resolving it (tests, forced deploys).
     * Pass null to disable the stamp entirely.
     */
    public static function pin(?string $short): void
    {
        self::$pinned = true;
        self::$short = self::normalize((string) $short);
    }

    /**
     * Drop the pinned/memoized value so the next call resolves again.
     */
    public static function flush(): void
    {
        self::$pinned = false;
        self::$resolved = false;
        self::$short = null;
    }

    /**
     * Read the commit id from the repository's HEAD (branches, packed
     * refs, detached HEADs and worktree `.git` pointers all resolve).
     */
    private static function fromGit(): ?string
    {
        $gitDir = base_path('.git');

        if (is_file($gitDir)) {
            $pointer = trim((string) @file_get_contents($gitDir));

            if (! str_starts_with($pointer, 'gitdir:')) {
                return null;
            }

            $gitDir = trim(substr($pointer, 7));
            $gitDir = str_starts_with($gitDir, '/') ? $gitDir : base_path($gitDir);
        }

        if (! is_dir($gitDir)) {
            return null;
        }

        $head = trim((string) @file_get_contents($gitDir.DIRECTORY_SEPARATOR.'HEAD'));

        if ($head === '') {
            return null;
        }

        if (! str_starts_with($head, 'ref:')) {
            return self::normalize($head); // detached HEAD
        }

        $ref = trim(substr($head, 4));
        $refFile = $gitDir.DIRECTORY_SEPARATOR.$ref;

        if (is_file($refFile)) {
            return self::normalize((string) @file_get_contents($refFile));
        }

        $packed = (string) @file_get_contents($gitDir.DIRECTORY_SEPARATOR.'packed-refs');

        foreach (explode("\n", $packed) as $line) {
            if (str_ends_with($line, ' '.$ref)) {
                return self::normalize(explode(' ', trim($line))[0] ?? '');
            }
        }

        return null;
    }

    /**
     * Normalize a full or abbreviated SHA to the 7-character stamp.
     */
    private static function normalize(string $sha): ?string
    {
        $sha = strtolower(trim($sha));

        return preg_match('/^[0-9a-f]{4,64}$/', $sha) === 1
            ? substr($sha, 0, 7)
            : null;
    }
}
