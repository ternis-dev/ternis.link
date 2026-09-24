<?php

namespace App\Services;

use App\Exceptions\JunkUrlException;

/**
 * Heuristic detector for scanner junk submitted as destination URLs.
 *
 * Vulnerability scanners probe the guest form, the public API and the
 * /url/* redirects with payloads like https://phpinfo.php,
 * https://info.php.bak or https://.env.backup1. Those are syntactically
 * valid URLs, so plain `url` validation passes — this detector scores
 * the probe SHAPE instead:
 *
 * - hosts that are filenames, not domains (final label is a file
 *   extension such as .php, or a backup marker such as .bak)
 * - malformed hosts (leading dot, tilde, missing domain extension)
 * - known scanner-probe filenames and keywords (phpinfo, .env,
 *   server-status, xmlrpc, wp-login, …)
 * - backup-copy suffixes on hosts and paths (*.bak, *~, …)
 *
 * Deliberately conservative: anything unrecognized passes. No DNS
 * lookups — they are slow, flaky, and legit-but-new domains often
 * don't resolve yet.
 */
final class JunkUrlDetector
{
    /**
     * Probe keywords flagged anywhere in the host, or in a short,
     * dash-free final path segment (blog slugs contain dashes and
     * pass through, e.g. /how-to-disable-phpinfo).
     */
    private const PROBE_KEYWORDS = [
        'phpinfo',
        'phpversion',
        'pinfo',
        'server-status',
        'server-info',
        'xmlrpc',
        'wp-login',
        'wp-admin',
        'wp-content',
        'wp-includes',
        'phpmyadmin',
        'adminer',
        'actuator',
        'eval-stdin',
        '.git/',
        '.svn/',
    ];

    /**
     * Exact probe filenames flagged as the final path segment
     * (case-insensitive).
     */
    private const PROBE_FILENAMES = [
        'phpinfo.php',
        'phpversion.php',
        'pinfo.php',
        'info.php',
        'pi.php',
        'p.php',
        'i.php',
        'php.php',
        'test.php',
        'debug.php',
        'xmlrpc.php',
        'wp-login.php',
        '.env',
        'server-status',
        'server-info',
    ];

    /**
     * Final host labels that are file extensions or backup markers —
     * never real top-level domains.
     */
    private const FILE_EXTENSION_TLDS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'phtml',
        'asp', 'aspx', 'jsp', 'cgi', 'pl', 'sh',
        'env', 'bak', 'old', 'save', 'swp', 'tmp',
        'backup', 'orig', 'sql', 'dump', 'log', 'conf', 'ini',
    ];

    private const BACKUP_LABEL_PATTERN = '/^(bak|backup|old|save|orig|tmp|temp|bkp|copy)\d*$/';

    private const DOTLESS_ALLOWLIST = ['localhost'];

    public function isJunk(string $url): bool
    {
        return $this->reasons($url) !== [];
    }

    /**
     * @return list<string> Human-readable reasons; empty when clean.
     */
    public function reasons(string $url): array
    {
        $parts = parse_url(trim($url));

        if (! is_array($parts)) {
            return []; // Unparseable — leave that to `url` validation.
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return []; // Other schemes are rejected elsewhere, not scored here.
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = strtolower((string) ($parts['path'] ?? ''));

        if ($host === '' || str_starts_with($host, '.') || str_contains($host, '~')) {
            return ['host is not a valid hostname'];
        }

        // IP hosts always pass (intranet dashboards, dev boxes); only
        // their paths are scored.
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return $this->pathReasons($path);
        }

        $reasons = [];

        if (! str_contains($host, '.')) {
            if (! in_array($host, self::DOTLESS_ALLOWLIST, true)) {
                return ['host has no domain extension'];
            }
        } else {
            $labels = explode('.', $host);
            $tld = (string) end($labels);

            if (in_array($tld, self::FILE_EXTENSION_TLDS, true)) {
                $reasons[] = "host ends in .{$tld}, a file extension rather than a domain";
            } elseif (preg_match(self::BACKUP_LABEL_PATTERN, $tld) === 1) {
                $reasons[] = "host ends in .{$tld}, which looks like a backup copy";
            }
        }

        foreach (self::PROBE_KEYWORDS as $keyword) {
            if (str_contains($host, $keyword)) {
                $reasons[] = "host matches the known scanner probe '{$keyword}'";
                break;
            }
        }

        if (str_contains($host, '.env')) {
            $reasons[] = 'host targets an environment (.env) file';
        }

        return array_merge($reasons, $this->pathReasons($path));
    }

    /**
     * Score the path: exact probe filenames, keyword hits in short
     * dash-free final segments, .env files and backup-copy suffixes.
     *
     * @return list<string>
     */
    private function pathReasons(string $path): array
    {
        if ($path === '' || $path === '/') {
            return [];
        }

        if (preg_match('#/\.env(\.|/|$)#', $path) === 1) {
            return ['path targets an environment (.env) file'];
        }

        $segments = array_values(array_filter(explode('/', $path), fn ($s) => $s !== ''));
        $last = (string) end($segments);

        if (in_array($last, self::PROBE_FILENAMES, true)) {
            return ["path ends in the known scanner probe '{$last}'"];
        }

        // Bare probe paths (/phpinfo.php) are short and dash-free;
        // article slugs (/how-to-disable-phpinfo) pass through.
        if ($last !== '' && strlen($last) <= 32 && ! str_contains($last, '-')) {
            foreach (self::PROBE_KEYWORDS as $keyword) {
                $needle = rtrim($keyword, '/');
                if ($needle !== '' && str_contains($last, $needle)) {
                    return ["path matches the known scanner probe '{$needle}'"];
                }
            }
        }

        if (str_ends_with($path, '~')
            || preg_match('#\.(bak|old|save|backup|swp|tmp|orig)(\.|$)#', $path) === 1) {
            return ['path looks like a backup copy'];
        }

        return [];
    }

    /**
     * @throws JunkUrlException
     */
    public function rejectIfJunk(string $url): void
    {
        $reasons = $this->reasons($url);

        if ($reasons !== []) {
            throw JunkUrlException::forUrl($url, $reasons);
        }
    }
}
