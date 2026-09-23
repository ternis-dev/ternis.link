<?php

namespace App\Services;

use App\Models\Domain;

/**
 * Verifies domain ownership via a DNS TXT record.
 *
 * The owner publishes:  _ternis-verify.<hostname>  TXT  <verification_token>
 */
class DomainVerificationService
{
    public const RECORD_PREFIX = '_ternis-verify';

    /**
     * @param  callable|null  $txtResolver  fn(string $fqdn): string[] — injectable for tests.
     */
    public function __construct(
        private mixed $txtResolver = null,
    ) {}

    public function verificationHost(Domain $domain): string
    {
        return self::RECORD_PREFIX.'.'.$domain->hostname;
    }

    /**
     * Instructions shown to the owner until the domain verifies.
     */
    public function instructions(Domain $domain): array
    {
        return [
            'type' => 'TXT',
            'host' => $this->verificationHost($domain),
            'value' => $domain->verification_token,
        ];
    }

    /**
     * Check DNS for the verification token. Marks the domain verified
     * on success.
     */
    public function verify(Domain $domain): bool
    {
        if ($domain->isVerified() || ! $domain->verification_token) {
            return $domain->isVerified();
        }

        foreach ($this->txtRecords($this->verificationHost($domain)) as $txt) {
            if (trim((string) $txt, '"') === $domain->verification_token) {
                $domain->markVerified();

                return true;
            }
        }

        return false;
    }

    /**
     * @return string[]
     */
    protected function txtRecords(string $fqdn): array
    {
        if ($this->txtResolver !== null) {
            return array_map(strval(...), (array) call_user_func($this->txtResolver, $fqdn));
        }

        $records = @dns_get_record($fqdn, DNS_TXT);

        if (! is_array($records)) {
            return [];
        }

        return array_column(array_filter($records, fn ($r) => isset($r['txt'])), 'txt');
    }
}
