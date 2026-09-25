<?php

namespace App\Livewire\Dashboard;

use App\Models\ActivityLog;
use App\Models\Domain;
use App\Services\DomainService;
use App\Support\Activity;
use App\Support\DomainUrls;
use App\Support\Notifier;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DomainManager extends Component
{
    public string $hostname = '';

    public ?string $justCreatedId = null;

    public string $subdomain = '';

    public ?string $claimedHostname = null;

    protected function rules(): array
    {
        return [
            'hostname' => [
                'required',
                'string',
                'max:255',
                'regex:/^(?!-)[a-z0-9-]{1,63}(?<!-)(\.(?!-)[a-z0-9-]{1,63}(?<!-))*\.[a-z]{2,}$/',
                'unique:domains,hostname',
            ],
        ];
    }

    protected function messages(): array
    {
        return [
            'hostname.regex' => 'The hostname must be a valid domain name (e.g. links.example.com).',
            'hostname.unique' => 'This hostname is already registered.',
            'subdomain.required' => 'Pick a name for your subdomain.',
            'subdomain.max' => 'That name is too long.',
        ];
    }

    /**
     * Claim a personal {name}.ternis.link subdomain. No DNS needed —
     * the app owns the parent zone, so claims are verified instantly.
     */
    public function claimSubdomain(DomainService $domains): void
    {
        $this->claimedHostname = null;
        $this->subdomain = strtolower(trim($this->subdomain));

        $this->validate([
            'subdomain' => ['required', 'string', 'max:63'],
        ]);

        try {
            $domain = $domains->claimSubdomain(auth()->user(), $this->subdomain);
        } catch (ValidationException $e) {
            $this->addError('subdomain', $e->validator->errors()->first('subdomain') ?? 'This name cannot be claimed.');

            return;
        } catch (HttpException $e) {
            $this->addError('subdomain', $e->getMessage());

            return;
        }

        $this->claimedHostname = $domain->hostname;
        $this->subdomain = '';

        Activity::record(ActivityLog::DOMAIN_CLAIMED, auth()->user(), $domain, [
            'hostname' => $domain->hostname,
        ]);
    }

    /**
     * Register a custom domain for the current user. Starts unverified;
     * DNS instructions are shown after creation until verified.
     */
    public function addDomain(DomainService $domains): void
    {
        $this->justCreatedId = null;
        $this->hostname = $domains->normalize($this->hostname);

        $this->validate();

        try {
            $domain = $domains->createForUser(auth()->user(), $this->hostname);
        } catch (ValidationException $e) {
            $this->addError('hostname', $e->validator->errors()->first('hostname') ?? 'This hostname cannot be registered.');

            return;
        } catch (HttpException $e) {
            $this->addError('hostname', $e->getMessage());

            return;
        }

        $this->justCreatedId = $domain->id;
        $this->hostname = '';

        Activity::record(ActivityLog::DOMAIN_REGISTERED, auth()->user(), $domain, [
            'hostname' => $domain->hostname,
        ]);
    }

    /**
     * Attempt DNS TXT verification for an owned domain.
     */
    public function verifyDomain(string $domainId, DomainService $domains): void
    {
        $domain = $this->ownedDomain($domainId);

        if (! $domain) {
            $this->addError("verify.{$domainId}", 'Domain not found.');

            return;
        }

        if (! $domains->verify($domain)) {
            $this->addError(
                "verify.{$domainId}",
                'Verification TXT record not found. Publish the record below, wait for DNS propagation, and retry.'
            );

            return;
        }

        Activity::record(ActivityLog::DOMAIN_VERIFIED, auth()->user(), $domain, [
            'hostname' => $domain->hostname,
        ]);

        Notifier::security(
            $domain->user ?? auth()->user(),
            'Domain verified: '.$domain->hostname,
            ["Your domain {$domain->hostname} passed DNS verification and can now serve short links."],
            DomainUrls::dashboard('/domains'),
            'View your domains',
        );
    }

    /**
     * Soft-deactivate an owned domain (links and analytics are preserved).
     */
    public function removeDomain(string $domainId, DomainService $domains): void
    {
        $domain = $this->ownedDomain($domainId);

        if (! $domain) {
            $this->addError('hostname', 'Domain not found.');

            return;
        }

        $domains->deactivate($domain);

        Activity::record(ActivityLog::DOMAIN_DEACTIVATED, auth()->user(), $domain, [
            'hostname' => $domain->hostname,
        ]);

        $owner = $domain->user ?? auth()->user();

        Notifier::security(
            $owner,
            'Domain deactivated: '.$domain->hostname,
            ["Your domain {$domain->hostname} was deactivated. Existing links and analytics are preserved."],
            DomainUrls::dashboard('/domains'),
            'View your domains',
        );
    }

    /**
     * Scope lookups to the current user's own active domains so tampered
     * IDs (foreign or system domains) resolve to null and become a no-op.
     */
    private function ownedDomain(string $domainId): ?Domain
    {
        return auth()->user()->domains()
            ->where('domains.id', $domainId)
            ->where('domains.is_active', true)
            ->first();
    }

    public function render()
    {
        $user = auth()->user();

        $systemDomains = Domain::where('is_active', true)
            ->whereNull('user_id')
            ->withCount('links')
            ->orderBy('hostname')
            ->get();

        $ownDomains = $user->domains()
            ->where('domains.is_active', true)
            ->withCount('links')
            ->orderBy('hostname')
            ->get();

        $verification = app(DomainService::class);
        $instructions = [];
        foreach ($ownDomains as $domain) {
            if (! $domain->isVerified()) {
                $instructions[$domain->id] = $verification->instructions($domain);
            }
        }

        $canAdd = $user->isAdmin() || (bool) $user->plan?->custom_subdomain;
        $planName = $user->plan?->name ?? 'current';

        $canClaimSubdomain = $user->canClaimSubdomain();
        $personalSubdomain = $user->domains()
            ->where('domains.hostname', 'like', '%.'.DomainService::SUBDOMAIN_ROOT)
            ->where('domains.is_active', true)
            ->orderBy('hostname')
            ->first();

        return view('livewire.dashboard.domain-manager', compact(
            'systemDomains', 'ownDomains', 'instructions', 'canAdd', 'planName',
            'canClaimSubdomain', 'personalSubdomain'
        ));
    }
}
