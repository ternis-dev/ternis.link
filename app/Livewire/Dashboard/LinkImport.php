<?php

namespace App\Livewire\Dashboard;

use App\Models\ActivityLog;
use App\Models\Domain;
use App\Services\JunkUrlDetector;
use App\Services\LinkService;
use App\Services\UnsafeUrlValidator;
use App\Support\Activity;
use App\Support\DomainUrls;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * CSV link import: paste rows, dry-run validation, then import.
 * Format per line: destination_url,domain_hostname,slug,expires_at,description,tags
 * (tags semicolon-separated). Max 200 rows; per-row results shown.
 */
class LinkImport extends Component
{
    public const MAX_ROWS = 200;

    public string $csv = '';

    /** @var list<array{row: int, ok: bool, message: string}> */
    public array $results = [];

    public bool $dryRun = true;

    /**
     * Dashboard scope, mirrors LinkTable: 'public' only accepts
     * public-dashboard hostnames, 'personal' rejects them (use the other
     * dashboard's importer instead).
     */
    public ?string $scope = null;

    public string $theme = 'dashboard';

    public function dryRunImport(LinkService $links, JunkUrlDetector $junk, UnsafeUrlValidator $unsafe): void
    {
        $this->results = $this->parseRows($links, $junk, $unsafe, dryRun: true);
        $this->dryRun = true;
    }

    public function import(LinkService $links): void
    {
        $this->results = $this->parseRows($links, app(JunkUrlDetector::class), app(UnsafeUrlValidator::class), dryRun: false);
        $this->dryRun = false;

        $created = collect($this->results)->where('ok', true)->count();
        $failed = count($this->results) - $created;

        $this->dispatch(
            'notify',
            message: $failed === 0
                ? "Imported {$created} link(s)."
                : "Imported {$created} link(s), {$failed} row(s) need attention.",
            type: $failed === 0 ? 'success' : 'error',
        );
    }

    /**
     * @return list<array{row: int, ok: bool, message: string}>
     */
    private function parseRows(LinkService $links, JunkUrlDetector $junk, UnsafeUrlValidator $unsafe, bool $dryRun): array
    {
        $user = auth()->user();
        $lines = preg_split('/\r\n|\r|\n/', trim((string) $this->csv));
        $results = [];
        $rowNumber = 0;

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $rowNumber++;

            if ($rowNumber > self::MAX_ROWS) {
                $results[] = ['row' => $rowNumber, 'ok' => false, 'message' => 'Row cap reached (200 rows max).'];

                break;
            }

            $fields = str_getcsv($line);
            $destination = trim((string) ($fields[0] ?? ''));
            $hostname = trim((string) ($fields[1] ?? ''));
            $slug = trim((string) ($fields[2] ?? ''));
            $expires = trim((string) ($fields[3] ?? ''));
            $description = trim((string) ($fields[4] ?? ''));
            $tags = trim((string) ($fields[5] ?? ''));

            $domain = $hostname !== ''
                ? Domain::where('hostname', strtolower($hostname))->first()
                : $this->defaultDomain();

            if (! $domain || ! $this->canUseDomain($domain)) {
                $results[] = ['row' => $rowNumber, 'ok' => false, 'message' => 'Unknown or unusable domain.'];

                continue;
            }

            // Enforce the public dashboard split: my.ternis.link only accepts
            // public hostnames; dash.ternis.link accepts all usable domains.
            $publicHosts = DomainUrls::publicDashboardHostnames();
            $isPublicHost = in_array($domain->hostname, $publicHosts, true);

            if ($this->scope === 'public' && ! $isPublicHost) {
                $results[] = ['row' => $rowNumber, 'ok' => false, 'message' => "Domain {$domain->hostname} is managed on dash.ternis.link."];

                continue;
            }

            try {
                $expiresAt = $expires !== '' ? new \DateTime($expires) : null;

                if ($expiresAt && $expiresAt <= now()) {
                    throw ValidationException::withMessages(['expires_at' => 'The expiration date must be in the future.']);
                }

                if ($dryRun) {
                    $this->validateRow($links, $junk, $unsafe, $destination, $domain, $slug !== '' ? $slug : null);
                } else {
                    $link = $links->create(
                        destinationUrl: $destination,
                        domain: $domain,
                        user: $user,
                        customSlug: $slug !== '' ? $slug : null,
                        expiresAt: $expiresAt,
                        description: $description !== '' ? $description : null,
                        tags: $tags !== '' ? str_replace(';', ',', $tags) : null,
                    );

                    Activity::record(ActivityLog::LINK_CREATED, $user, $link, [
                        'slug' => $link->slug,
                        'domain' => $domain->hostname,
                        'via' => 'dashboard-import',
                    ]);
                }

                $results[] = ['row' => $rowNumber, 'ok' => true, 'message' => $dryRun ? 'Valid.' : "Created {$domain->hostname}/".($slug !== '' ? $slug : '(auto)'.'')];
            } catch (ValidationException $e) {
                $results[] = ['row' => $rowNumber, 'ok' => false, 'message' => implode(' ', array_map(fn ($m) => implode(' ', (array) $m), $e->errors()))];
            } catch (\Throwable $e) {
                $results[] = ['row' => $rowNumber, 'ok' => false, 'message' => 'Failed: '.$e->getMessage()];
            }
        }

        if ($rowNumber === 0) {
            $results[] = ['row' => 0, 'ok' => false, 'message' => 'Paste at least one row first.'];
        }

        return $results;
    }

    private function validateRow(LinkService $links, JunkUrlDetector $junk, UnsafeUrlValidator $unsafe, string $destination, Domain $domain, ?string $slug): void
    {
        if ($destination === '' || filter_var($destination, FILTER_VALIDATE_URL) === false) {
            throw ValidationException::withMessages(['destination_url' => 'Invalid destination URL.']);
        }

        // Same safety net as creation, so dry-run verdicts match import.
        $unsafe->rejectIfUnsafe($destination);
        $junk->rejectIfJunk($destination);

        if ($slug !== null && ! $links->slugAvailable($slug, $domain->id)) {
            throw ValidationException::withMessages(['slug' => 'Slug already taken on this domain.']);
        }
    }

    private function defaultDomain(): ?Domain
    {
        $user = auth()->user();
        if ($user) {
            $default = $user->resolvedDefaultDomain($this->scope);
            if ($default && $this->canUseDomain($default)) {
                return $default;
            }
        }

        // The fallback must be importable under the active scope:
        // href.nz belongs to the public dashboard, clicked.at to dash.
        $hostname = $this->scope === 'public'
            ? config('domains.public_host', 'href.nz')
            : ($this->scope === 'personal' ? 'clicked.at' : config('domains.public_host', 'href.nz'));

        return Domain::where('hostname', $hostname)->first();
    }

    private function canUseDomain(Domain $domain): bool
    {
        $user = auth()->user();

        if (! $domain->is_active) {
            return false;
        }

        if ($domain->isSystemDomain()) {
            return true;
        }

        return $user->isAdmin() || ($domain->user_id === $user->id && $domain->isVerified());
    }

    public function render()
    {
        return view('livewire.dashboard.link-import');
    }
}
