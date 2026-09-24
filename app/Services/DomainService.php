<?php

namespace App\Services;

use App\Enums\DomainType;
use App\Models\Domain;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DomainService
{
    public const HOSTNAME_PATTERN = '/^(?!-)[a-z0-9-]{1,63}(?<!-)(\.(?!-)[a-z0-9-]{1,63}(?<!-))*\.[a-z]{2,}$/';

    /**
     * Parent zone for personal subdomains. The app serves *.ternis.link
     * (Caddy) and resolves them via wildcard_roots, so claims need no
     * DNS proof — ownership of the parent is proof enough.
     */
    public const SUBDOMAIN_ROOT = 'ternis.link';

    public const SUBDOMAIN_LABEL_PATTERN = '/^[a-z0-9][a-z0-9-]{1,61}[a-z0-9]$/';

    /**
     * Single labels under ternis.link that can never be claimed.
     */
    public const RESERVED_SUBDOMAIN_LABELS = [
        'www', 'mail', 'ftp', 'api', 'app', 'dash', 'dashboard', 'admin',
        'auth', 'login', 'logout', 'sso', 'links', 'link', 'go', 'short',
        'url', 'static', 'cdn', 'assets', 'status', 'health', 'healthz',
        'metrics', 'support', 'help', 'docs', 'blog', 'dev', 'test',
        'staging', 'prod', 'webhook', 'webhooks', 'billing', 'abuse',
        'postmaster', 'hostmaster', 'security', 'privacy', 'legal',
        'ternis',
    ];

    public function __construct(
        private DomainVerificationService $verification,
    ) {}

    /**
     * Normalize user input: lowercase, trim whitespace/trailing dot.
     */
    public function normalize(string $hostname): string
    {
        return rtrim(strtolower(trim($hostname)), '.');
    }

    public function isValidHostname(string $hostname): bool
    {
        return strlen($hostname) <= 255
            && preg_match(self::HOSTNAME_PATTERN, $hostname) === 1;
    }

    /**
     * System hostnames and protected subtrees can never be claimed,
     * nor can special-use TLDs that never resolve publicly.
     */
    public function isReserved(string $hostname): bool
    {
        $reserved = array_merge(array_keys(config('domains.map', [])), ['api.ternis.link']);

        if (in_array($hostname, $reserved, true)) {
            return true;
        }

        foreach (['.dash.ternis.link', '.admin.ternis.link', '.api.ternis.link', '.links.t-api.de'] as $suffix) {
            if (str_ends_with($hostname, $suffix)) {
                return true;
            }
        }

        $tld = substr(strrchr($hostname, '.'), 1);

        return in_array($tld, ['localhost', 'local', 'test', 'invalid', 'example', 'internal'], true);
    }

    /**
     * Register a user-owned (partner-type) domain. Starts unverified;
     * the owner must publish the TXT record, then call verify().
     *
     * @throws ValidationException
     */
    public function createForUser(User $user, string $hostname): Domain
    {
        $hostname = $this->normalize($hostname);

        if (! $this->isValidHostname($hostname)) {
            throw ValidationException::withMessages([
                'hostname' => 'The hostname must be a valid domain name (e.g. links.example.com).',
            ]);
        }

        if ($this->isReserved($hostname)) {
            throw ValidationException::withMessages([
                'hostname' => 'This hostname is reserved and cannot be registered.',
            ]);
        }

        if (Domain::where('hostname', $hostname)->exists()) {
            throw ValidationException::withMessages([
                'hostname' => 'This hostname is already registered.',
            ]);
        }

        if (! $user->isAdmin() && ! $user->plan?->custom_subdomain) {
            $planName = $user->plan?->name ?? 'current';
            abort(403, "Your plan ({$planName}) does not include custom domains.");
        }

        return Domain::create([
            'hostname' => $hostname,
            'user_id' => $user->id,
            'verification_token' => Str::random(32),
            'type' => DomainType::Partner,
            'is_active' => true,
        ]);
    }

    /**
     * Claim a personal {name}.ternis.link subdomain. Reserved for the
     * inner circle (admin/family/partner roles) — one active subdomain
     * per account. Created already verified: the app controls the
     * parent zone (wildcard DNS + wildcard_roots), so no TXT proof
     * is needed and the domain is usable for links immediately.
     *
     * @throws ValidationException
     */
    public function claimSubdomain(User $user, string $name): Domain
    {
        if (! $user->canClaimSubdomain()) {
            abort(403, 'Personal ternis.link subdomains are available to family, partner and admin accounts.');
        }

        $label = strtolower(trim($name, " \t\n\r\0\x0B."));

        if (! preg_match(self::SUBDOMAIN_LABEL_PATTERN, $label)) {
            throw ValidationException::withMessages([
                'subdomain' => 'Use 3–63 lowercase letters, numbers and dashes.',
            ]);
        }

        $hostname = $label.'.'.self::SUBDOMAIN_ROOT;

        if (in_array($label, self::RESERVED_SUBDOMAIN_LABELS, true) || $this->isReserved($hostname)) {
            throw ValidationException::withMessages([
                'subdomain' => "“{$label}” is reserved — pick another name.",
            ]);
        }

        if (Domain::where('hostname', $hostname)->exists()) {
            throw ValidationException::withMessages([
                'subdomain' => 'This subdomain is already taken.',
            ]);
        }

        $hasOne = $user->domains()
            ->where('hostname', 'like', '%.'.self::SUBDOMAIN_ROOT)
            ->where('is_active', true)
            ->exists();

        if ($hasOne) {
            throw ValidationException::withMessages([
                'subdomain' => 'You already have a personal subdomain — one per account.',
            ]);
        }

        return Domain::create([
            'hostname' => $hostname,
            'user_id' => $user->id,
            'verification_token' => null,
            'type' => DomainType::Ternis,
            'is_active' => true,
            'verified_at' => now(),
        ]);
    }

    /**
     * Attempt DNS verification for an owner/admin-held domain.
     */
    public function verify(Domain $domain): bool
    {
        return $this->verification->verify($domain->fresh());
    }

    public function instructions(Domain $domain): array
    {
        return $this->verification->instructions($domain);
    }

    /**
     * Soft-deactivate a domain (links and analytics are preserved).
     */
    public function deactivate(Domain $domain): void
    {
        $domain->update(['is_active' => false]);
    }
}
