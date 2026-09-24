<?php

declare(strict_types=1);

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
     * File extensions and backup markers masquerading as TLDs.
     */
    private const FILE_EXTENSION_TLDS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml',
        'asp', 'aspx', 'jsp', 'jspx', 'cgi', 'pl', 'py', 'rb', 'sh', 'bash',
        'env', 'bak', 'old', 'save', 'swp', 'tmp', 'temp', 'bkp',
        'backup', 'orig', 'sql', 'dump', 'log', 'conf', 'ini', 'config',
        'dist', 'local', 'example', 'git', 'svn', 'yml', 'yaml', 'json',
    ];

    /**
     * Scanner probe tokens that flag a match if present in the host
     * or inside short, dash-free path segments.
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
    ];

    /**
     * Filename targets commonly probed at root or as the final path segment.
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
        'web.config',
        'database.yml',
        'pma',
    ];

    private const BACKUP_LABEL_PATTERN = '/^(bak|backup|old|save|orig|tmp|temp|bkp|copy)\d*$/i';

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
        $trimmed = trim($url);
        if ($trimmed === '') {
            return [];
        }

        $parts = parse_url($trimmed);
        if (! is_array($parts)) {
            return [];
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return [];
        }

        $rawHost = (string) ($parts['host'] ?? '');
        $path = (string) ($parts['path'] ?? '');

        if ($rawHost === '') {
            return ['missing host component'];
        }

        $host = strtolower($rawHost);

        // Discard scanner anomalies directly in the hostname
        if (
            str_starts_with($host, '.') ||
            str_ends_with($host, '.') ||
            str_contains($host, '~') ||
            str_contains($host, '..')
        ) {
            return ['host contains invalid or scanner-probe characters'];
        }

        // IP hosts bypass domain checks; only path heuristics apply
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return $this->pathReasons($path);
        }

        $reasons = [];

        // Catch payloads where the entire host is a probe filename (e.g., https://i.php, https://test.php)
        if (in_array($host, self::PROBE_FILENAMES, true)) {
            $reasons[] = "host is a targeted scanner probe file '{$host}'";
        }

        if (! str_contains($host, '.')) {
            if (! in_array($host, self::DOTLESS_ALLOWLIST, true)) {
                $reasons[] = 'host has no domain extension';
            }
        } else {
            $labels = explode('.', $host);
            $tld = end($labels);

            if (in_array($tld, self::FILE_EXTENSION_TLDS, true)) {
                $reasons[] = "host ends in .{$tld}, a file extension rather than a valid TLD";
            } elseif (preg_match(self::BACKUP_LABEL_PATTERN, (string) $tld) === 1) {
                $reasons[] = "host ends in .{$tld}, which matches backup naming conventions";
            }
        }

        foreach (self::PROBE_KEYWORDS as $keyword) {
            if (str_contains($host, $keyword)) {
                $reasons[] = "host contains probe keyword '{$keyword}'";
                break;
            }
        }

        if (str_contains($host, '.env')) {
            $reasons[] = 'host targets an environment configuration file';
        }

        return array_merge($reasons, $this->pathReasons($path));
    }

    /**
     * @return list<string>
     */
    private function pathReasons(string $path): array
    {
        $rawPath = trim($path);
        if ($rawPath === '' || $rawPath === '/') {
            return [];
        }

        $normalized = strtolower(rawurldecode($rawPath));

        if (preg_match('#(?:^|/)\.env(?:\.|$|/)#', $normalized) === 1) {
            return ['path targets an environment (.env) file'];
        }

        if (preg_match('#(?:^|/)\.(?:git|svn)(?:/|$)#', $normalized) === 1) {
            return ['path targets source control repository metadata'];
        }

        if (
            str_ends_with($normalized, '~') ||
            preg_match('#\.(?:bak|old|save|backup|swp|tmp|temp|bkp)(?:\.|/|$)|(?<!/)\.orig(?:\.bak|\.old|/|$)#', $normalized) === 1
        ) {
            return ['path targets a backup or editor temporary artifact'];
        }

        $segments = array_values(array_filter(explode('/', $normalized), static fn (string $s): bool => $s !== ''));
        if ($segments === []) {
            return [];
        }

        $last = end($segments);

        if (in_array($last, self::PROBE_FILENAMES, true)) {
            return ["path ends in targeted probe file '{$last}'"];
        }

        // Short, dash-free segments (e.g., /phpinfo) are scanner probes; hyphenated slugs pass
        if (strlen($last) <= 32 && ! str_contains($last, '-')) {
            foreach (self::PROBE_KEYWORDS as $keyword) {
                if (str_contains($last, $keyword)) {
                    return ["path segment matches scanner probe keyword '{$keyword}'"];
                }
            }
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
