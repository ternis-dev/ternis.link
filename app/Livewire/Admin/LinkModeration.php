<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithTableColumns;
use App\Models\ActivityLog;
use App\Models\Link;
use App\Support\Activity;
use App\Support\DomainUrls;
use App\Support\Notifier;
use Livewire\Component;
use Livewire\WithPagination;

class LinkModeration extends Component
{
    use WithPagination;
    use WithTableColumns;

    public string $search = '';

    public string $status = 'all';

    public string $sortBy = 'created_at';

    public string $sortDir = 'desc';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
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

    public function render()
    {
        $this->ensureAdmin();

        $links = Link::with(['domain', 'user'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('slug', 'like', "%{$this->search}%")
                        ->orWhere('destination_url', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhere('tags', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status === 'active', fn ($q) => $q->where('is_active', true)->where(fn ($s) => $s->whereNull('expires_at')->orWhere('expires_at', '>', now())))
            ->when($this->status === 'disabled', fn ($q) => $q->where('is_active', false)->where('is_removed', false))
            ->when($this->status === 'removed', fn ($q) => $q->where('is_removed', true))
            ->when($this->status === 'expired', fn ($q) => $q->whereNotNull('expires_at')->where('expires_at', '<=', now()))
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate(20);

        return view('livewire.admin.link-moderation', [
            'links' => $links,
            'availableColumns' => $this->availableColumns(),
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
        ];
    }

    private function ensureAdmin(): void
    {
        if (! auth()->check() || ! auth()->user()->isAdmin()) {
            abort(403, 'Admin access required.');
        }
    }
}
