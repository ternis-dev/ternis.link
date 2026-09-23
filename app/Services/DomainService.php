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
