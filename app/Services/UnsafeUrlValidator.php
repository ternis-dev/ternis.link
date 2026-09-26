<?php

namespace App\Services;

use App\Exceptions\UnsafeUrlException;

/**
 * Server-side destination safety for guest-created links.
 *
 * Complements JunkUrlDetector (scanner-shape heuristics): this checks
 * structural safety — scheme, embedded credentials, and whether the
 * target can even be a public website (no loopback / private /
 * link-local IPs, no localhost or intranet hostnames).
 *
 * Only literal checks run here — no DNS resolution in the request
 * path (slow, and a resolver is itself an SSRF surface). DNS-based
 * hostnames that rebind to private IPs are therefore NOT covered;
 * treat this as one layer, not a guarantee.
 */
class UnsafeUrlValidator
{
    /**
     * Intranet / special-use suffixes that never address the public web.
     *
     * @var list<string>
     */
    private const INTRANET_SUFFIXES = [
        '.localhost',
        '.local',
        '.internal',
        '.intranet',
        '.lan',
        '.home',
        '.corp',
        '.localdomain',
        '.invalid',
        '.example',
        '.test',
    ];

    public function rejectIfUnsafe(string $url): void
    {
        $url = trim($url);
        $parts = parse_url($url);

        if (! is_array($parts)) {
            throw UnsafeUrlException::forUrl($url, 'unparseable');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw UnsafeUrlException::forUrl($url, 'scheme');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw UnsafeUrlException::forUrl($url, 'credentials');
        }

        $host = strtolower((string) ($parts['host'] ?? ''));

        // parse_url keeps IPv6 brackets — strip one pair for checks.
        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $host = substr($host, 1, -1);
        }

        if ($host === '') {
            throw UnsafeUrlException::forUrl($url, 'empty-host');
        }

        if ($this->isBlockedIpLiteral($host)) {
            throw UnsafeUrlException::forUrl($url, 'non-public-ip');
        }

        if ($host === 'localhost'
            || str_ends_with($host, '.localhost')
            || ! str_contains($host, '.')
            || $this->hasIntranetSuffix($host)
        ) {
            throw UnsafeUrlException::forUrl($url, 'intranet-host');
        }
    }

    private function hasIntranetSuffix(string $host): bool
    {
        foreach (self::INTRANET_SUFFIXES as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return true;
            }
        }

        return false;
    }

    private function isBlockedIpLiteral(string $host): bool
    {
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // IPv4-mapped IPv6 would have colons; pure v4 here.
            return $this->isBlockedIpv4(ip2long($host));
        }

        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->isBlockedIpv6($host);
        }

        return false;
    }

    private function isBlockedIpv4(int $long): bool
    {
        $in = static fn (string $net, int $bits): bool => ($long >> (32 - $bits)) === (ip2long($net) >> (32 - $bits));

        return $in('10.0.0.0', 8)        // private
            || $in('172.16.0.0', 12)     // private
            || $in('192.168.0.0', 16)    // private
            || $in('127.0.0.0', 8)       // loopback
            || $in('169.254.0.0', 16)    // link-local
            || $in('0.0.0.0', 8)         // unspecified
            || $in('100.64.0.0', 10)     // carrier-grade NAT
            || $in('192.0.0.0', 24)      // IETF protocol assignments
            || $in('192.0.2.0', 24)      // TEST-NET-1 (documentation)
            || $in('198.18.0.0', 15)     // benchmark testing
            || $in('198.51.100.0', 24)   // TEST-NET-2 (documentation)
            || $in('203.0.113.0', 24)    // TEST-NET-3 (documentation)
            || $in('224.0.0.0', 4)       // multicast
            || $in('240.0.0.0', 4);      // reserved
    }

    private function isBlockedIpv6(string $host): bool
    {
        $packed = inet_pton($host);

        if ($packed === false || strlen($packed) !== 16) {
            return true; // Shouldn't happen after validation; stay safe.
        }

        $b = array_map(ord(...), str_split($packed));

        // ::ffff:0.0.0.0/96 — IPv4-mapped: judge the embedded v4 address.
        $isMapped = true;
        for ($i = 0; $i < 10; $i++) {
            if ($b[$i] !== 0x00) {
                $isMapped = false;
                break;
            }
        }

        if ($isMapped && $b[10] === 0xff && $b[11] === 0xff) {
            $v4 = ($b[12] << 24) | ($b[13] << 16) | ($b[14] << 8) | $b[15];

            return $this->isBlockedIpv4($v4);
        }

        if ($host === '::1' || $host === '::') {
            return true; // loopback / unspecified
        }

        if (($b[0] & 0xfe) === 0xfc) {
            return true; // fc00::/7 unique local
        }

        if ($b[0] === 0xfe && ($b[1] & 0xc0) === 0x80) {
            return true; // fe80::/10 link-local
        }

        if ($b[0] === 0xff) {
            return true; // ff00::/8 multicast
        }

        if ($b[0] === 0x20 && $b[1] === 0x01 && $b[2] === 0x0d && $b[3] === 0xb8) {
            return true; // 2001:db8::/32 documentation
        }

        return false;
    }
}
