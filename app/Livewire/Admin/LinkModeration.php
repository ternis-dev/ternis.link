<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithTableColumns;
use App\Models\ActivityLog;
use App\Models\Domain;
use App\Models\Link;
use App\Models\LinkTombstone;
use App\Support\Activity;
use App\Support\DomainUrls;
use App\Support\NetworkStats;
use App\Support\Notifier;
use Livewire\Component;
use Livewire\WithPagination;

class LinkModeration extends Component
{
    use WithPagination;
    use WithTableColumns;

    public string $search = '';

    public string $status = 'all';

    public string $domainFilter = '';

    public string $clicksFilter = 'all';

    public string $sortBy = 'created_at';

    public string $sortDir = 'desc';

    public string $sortOption = 'newest';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingDomainFilter(): void
    {
        $this->resetPage();
    }

    public function updatingClicksFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSortOption(string $val): void
    {
        match ($val) {
            'most_clicks' => [$this->sortBy, $this->sortDir] = ['click_count', 'desc'],
            'least_clicks' => [$this->sortBy, $this->sortDir] = ['click_count', 'asc'],
            'newest' => [$this->sortBy, $this->sortDir] = ['created_at', 'desc'],
            'oldest' => [$this->sortBy, $this->sortDir] = ['created_at', 'asc'],
            'slug_asc' => [$this->sortBy, $this->sortDir] = ['slug', 'asc'],
            'slug_desc' => [$this->sortBy, $this->sortDir] = ['slug', 'desc'],
            default => null,
        };
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->status = 'all';
        $this->domainFilter = '';
        $this->clicksFilter = 'all';
        $this->sortOption = 'newest';
        $this->sortBy = 'created_at';
        $this->sortDir = 'desc';
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'desc';
        }

        if ($this->sortBy === 'click_count') {
            $this->sortOption = $this->sortDir === 'desc' ? 'most_clicks' : 'least_clicks';
        } elseif ($this->sortBy === 'created_at') {
            $this->sortOption = $this->sortDir === 'desc' ? 'newest' : 'oldest';
        } elseif ($this->sortBy === 'slug') {
            $this->sortOption = $this->sortDir === 'asc' ? 'slug_asc' : 'slug_desc';
        }
    }

    public function deactivate(string $linkId): void
    {
        $this->ensureAdmin();

        $link = Link::findOrFail($linkId);
        $link->update(['is_active' => false]);

        Activity::record(ActivityLog::ADMIN_LINK_DEACTIVATED, auth()->user(), $link, [
            'slug' => $link->slug,
        ]);

        if ($link->user) {
            Notifier::security(
                $link->user,
                'Your link was deactivated',
                ["The link {$link->slug} was deactivated by an administrator. Existing analytics are preserved."],
                DomainUrls::dashboard('/links'),
                'View your links',
            );

            $this->dispatch('notify', message: "Link {$link->slug} deactivated.", type: 'info');
        }
    }

    public function reactivate(string $linkId): void
    {
        $this->ensureAdmin();

        $link = Link::findOrFail($linkId);
        $link->update(['is_active' => true]);

        Activity::record(ActivityLog::ADMIN_LINK_REACTIVATED, auth()->user(), $link, [
            'slug' => $link->slug,
        ]);

        if ($link->user) {
            Notifier::security(
                $link->user,
                'Your link was reactivated',
                ["The link {$link->slug} was reactivated by an administrator."],
                DomainUrls::dashboard('/links'),
                'View your links',
            );
        }
    }

    public function unquarantine(string $linkId): void
    {
        $this->ensureAdmin();

        $link = Link::findOrFail($linkId);
        $tags = is_array($link->tags) ? $link->tags : [];
        $tags = array_values(array_diff($tags, ['quarantine', 'malware', 'phishing', 'spam']));
        if (! in_array('admin_cleared', $tags, true)) {
            $tags[] = 'admin_cleared';
        }

        $link->update([
            'is_active' => true,
            'tags' => $tags,
        ]);

        Link::forgetCachedSlug((string) $link->domain_id, (string) $link->slug);

        Activity::record(ActivityLog::ADMIN_LINK_REACTIVATED, auth()->user(), $link, [
            'slug' => $link->slug,
            'reason' => 'Threat quarantine cleared via link moderation',
        ]);

        if ($link->user) {
            Notifier::security(
                $link->user,
                'Security Status Updated: Link Restored',
                [
                    "Your link \"/{$link->slug}\" pointing to \"{$link->destination_url}\" was reviewed by an administrator and restored.",
                    'The quarantine status has been removed and the link is active again.',
                ],
                DomainUrls::dashboard('/links/'.$link->id),
                'View Link'
            );
        }

        $this->dispatch('notify', message: "Link {$link->slug} unquarantined and reactivated.", type: 'success');
    }

    /**
     * Remove a link from every surface (redirects, dashboards, API).
     * The row — and its analytics — are preserved for stats and audit.
     */
    public function remove(string $linkId): void
    {
        $this->ensureAdmin();

        $link = Link::findOrFail($linkId);
        $link->update(['is_removed' => true]);
        Link::forgetCachedSlug($link->domain_id, $link->slug);

        Activity::record(ActivityLog::ADMIN_LINK_REMOVED, auth()->user(), $link, [
            'slug' => $link->slug,
        ]);

        if ($link->user) {
            Notifier::security(
                $link->user,
                'Your link was removed',
                ["The link {$link->slug} was removed by an administrator. It no longer resolves, but its stats are preserved."],
                DomainUrls::dashboard('/links'),
                'View your links',
            );

            $this->dispatch('notify', message: "Link {$link->slug} removed.", type: 'error');
        }
    }

    public function restore(string $linkId): void
    {
        $this->ensureAdmin();

        $link = Link::findOrFail($linkId);
        $link->update(['is_removed' => false]);

        Activity::record(ActivityLog::ADMIN_LINK_RESTORED, auth()->user(), $link, [
            'slug' => $link->slug,
        ]);

        if ($link->user) {
            Notifier::security(
                $link->user,
                'Your link was restored',
                ["The link {$link->slug} was restored by an administrator and resolves again."],
                DomainUrls::dashboard('/links'),
                'View your links',
            );
        }
    }

    /**
     * Permanently delete a removed link: the row and its click details
     * (IPs, referrers, user agents) are destroyed via cascade. A
     * tombstone preserves the aggregates first so every creation stays
     * counted in stats. Only removed links qualify — removal is the
     * reversible first step, deletion the deliberate second one.
     */
    public function delete(string $linkId): void
    {
        $this->ensureAdmin();

        $link = Link::with('domain')->findOrFail($linkId);

        if (! $link->is_removed) {
            $this->addError('delete', 'Remove the link first — permanent deletion is only available for removed links.');

            return;
        }

        $slug = $link->slug;
        $owner = $link->user;

        Activity::record(ActivityLog::ADMIN_LINK_DELETED, auth()->user(), $link, [
            'slug' => $slug,
        ]);

        LinkTombstone::snapshot($link);
        $link->delete();
        NetworkStats::flush();

        if ($owner) {
            Notifier::security(
                $owner,
                'Your link was permanently deleted',
                ["The link {$slug} was permanently deleted by an administrator. Its stats survive in anonymized aggregates."],
                DomainUrls::dashboard('/links'),
                'View your links',
            );
        }
    }

    public function render()
    {
        $this->ensureAdmin();

        $links = Link::with(['domain', 'user', 'apiKey.user', 'threatEvaluations'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('slug', 'like', "%{$this->search}%")
                        ->orWhere('destination_url', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhere('tags', 'like', "%{$this->search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$this->search}%")->orWhere('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->status === 'active', fn ($q) => $q->where('is_active', true)->where(fn ($s) => $s->whereNull('expires_at')->orWhere('expires_at', '>', now())))
            ->when($this->status === 'disabled', fn ($q) => $q->where('is_active', false)->where('is_removed', false))
            ->when($this->status === 'quarantined', fn ($q) => $q->where('tags', 'like', '%quarantine%'))
            ->when($this->status === 'removed', fn ($q) => $q->where('is_removed', true))
            ->when($this->status === 'expired', fn ($q) => $q->whereNotNull('expires_at')->where('expires_at', '<=', now()))
            ->when($this->domainFilter !== '', fn ($q) => $q->whereHas('domain', fn ($d) => $d->where('hostname', $this->domainFilter)))
            ->when($this->clicksFilter === 'has_clicks', fn ($q) => $q->where('click_count', '>', 0))
            ->when($this->clicksFilter === '100_plus', fn ($q) => $q->where('click_count', '>=', 100))
            ->when($this->clicksFilter === '10_plus', fn ($q) => $q->where('click_count', '>=', 10))
            ->when($this->clicksFilter === 'zero', fn ($q) => $q->where('click_count', 0))
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate(20);

        return view('livewire.admin.link-moderation', [
            'links' => $links,
            'domains' => Domain::orderBy('hostname')->pluck('hostname')->all(),
            'visibleColumns' => $visible = $this->visibleColumns(),
            'columnCount' => count($visible) + 1, // + fixed Actions column
        ]);
    }

    protected function tableKey(): string
    {
        return 'admin.links';
    }

    protected function availableColumns(): array
    {
        return [
            'slug' => 'Slug',
            'destination' => 'Destination',
            'domain' => 'Domain',
            'owner' => 'Owner',
            'clicks' => 'Clicks',
            'status' => 'Status',
            'security' => 'Security / AI',
        ];
    }

    private function ensureAdmin(): void
    {
        if (! auth()->check() || ! auth()->user()->isAdmin()) {
            abort(403, 'Admin access required.');
        }
    }
}
