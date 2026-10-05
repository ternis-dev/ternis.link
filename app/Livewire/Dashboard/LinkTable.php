<?php

namespace App\Livewire\Dashboard;

use App\Livewire\Concerns\WithTableColumns;
use App\Models\ActivityLog;
use App\Models\Link;
use App\Services\LinkService;
use App\Support\Activity;
use App\Support\DomainUrls;
use Livewire\Component;
use Livewire\WithPagination;

class LinkTable extends Component
{
    use WithPagination;
    use WithTableColumns;

    /**
     * Refresh when a link is created through the quick-create modal
     * rendered on the same page.
     */
    protected $listeners = ['link-created' => '$refresh'];

    public string $search = '';

    public string $tag = '';

    /**
     * API-key filter: '' = all visible, 'none' = dashboard-created
     * (no key), otherwise an owned ApiKey ULID.
     */
    public string $apiKeyFilter = '';

    /**
     * Locked key scope for the per-key page: when set, the filter
     * dropdown is hidden and the query is pinned to this key.
     */
    public ?string $lockedApiKeyId = null;

    public string $sortBy = 'created_at';

    public string $sortDir = 'desc';

    /**
     * Dashboard scope: null = legacy global (all own links),
     * 'public' = only my.href.nz hostnames (href.nz, meinlink.at,
     * href.yt, qr.href.nz), 'personal' = everything else (dash side of
     * the split: clicked.at, ternis.link, href.re, partner, custom).
     */
    public ?string $scope = null;

    /**
     * Selected link ids for bulk actions (page-scoped, verified
     * against ownership at execution time).
     *
     * @var list<string>
     */
    public array $selected = [];

    public ?string $bulkNotice = null;

    /** @var list<string> Ids on the current page (for select-all). */
    public array $pageIds = [];

    /**
     * Columns allowed for sorting (prevents arbitrary orderBy injection
     * via the public Livewire properties).
     */
    private const SORTABLE = ['slug', 'click_count', 'created_at'];

    protected function tableKey(): string
    {
        return 'dashboard.links';
    }

    protected function availableColumns(): array
    {
        return [
            'slug' => 'Short Link',
            'destination' => 'Destination URL',
            'domain' => 'Domain',
            'clicks' => 'Clicks',
            'status' => 'Status',
            'created' => 'Created',
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTag(): void
    {
        $this->resetPage();
    }

    public function updatingApiKeyFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->tag = '';
        if ($this->lockedApiKeyId === null) {
            $this->apiKeyFilter = '';
        }
        $this->resetPage();
    }

    public function mount(?string $apiKeyId = null, ?string $scope = null): void
    {
        // Embedded on the per-key page: pin the scope and mirror it
        // into the filter so the query below needs a single branch.
        if ($apiKeyId !== null && $apiKeyId !== '') {
            $this->lockedApiKeyId = $apiKeyId;
            $this->apiKeyFilter = $apiKeyId;
        }

        if (in_array($scope, ['public', 'personal'], true)) {
            $this->scope = $scope;
        }
    }

    public function sort(string $column): void
    {
        if (! in_array($column, self::SORTABLE, true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'desc';
        }
    }

    public function deactivate(string $linkId): void
    {
        // Strictly per-user: cross-user moderation happens on the
        // admin host (Admin\LinkModeration), never from dash.
        $link = auth()->user()->links()->notRemoved()->findOrFail($linkId);
        app(LinkService::class)->deactivate($link);

        Activity::record(ActivityLog::LINK_DEACTIVATED, auth()->user(), $link, [
            'slug' => $link->slug,
            'via' => 'dashboard',
        ]);
    }

    public function toggleSelectAll(bool $select): void
    {
        $this->selected = $select ? $this->pageIds : [];
    }

    public function bulkSetActive(LinkService $links, bool $active): void
    {
        $user = auth()->user();
        $ids = array_slice(array_unique($this->selected), 0, 200);
        $done = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            $link = $user->links()->notRemoved()->find($id);

            if (! $link) {
                $skipped++;

                continue;
            }

            $link->update(['is_active' => $active]);
            Link::forgetCachedSlug($link->domain_id, $link->slug);
            $done++;
        }

        Activity::record(
            $active ? ActivityLog::LINK_UPDATED : ActivityLog::LINK_DEACTIVATED,
            $user,
            null,
            ['via' => 'dashboard', 'bulk' => true, 'count' => $done, 'is_active' => $active]
        );

        $this->selected = [];
        $this->bulkNotice = $done === 0
            ? 'No eligible links selected.'
            : "{$done} link(s) ".($active ? 'activated' : 'deactivated').($skipped > 0 ? " ({$skipped} skipped)." : '.');
    }

    public function render()
    {
        $sortBy = in_array($this->sortBy, self::SORTABLE, true) ? $this->sortBy : 'created_at';
        $sortDir = $this->sortDir === 'asc' ? 'asc' : 'desc';

        $user = auth()->user();
        $base = $user->links()->notRemoved();

        // Owned keys for the origin filter (id => label). Revoked keys
        // stay listed so their historical links remain findable.
        $apiKeys = $user->apiKeys()->orderByDesc('created_at')->get(['id', 'name', 'key_prefix', 'show_on_dashboard']);

        // A locked per-key page trusts its own mount value; the free
        // filter only accepts owned keys (or 'none') and falls back
        // to "all visible" on anything else.
        $effectiveFilter = $this->lockedApiKeyId ?? $this->apiKeyFilter;
        if ($this->lockedApiKeyId === null
            && $effectiveFilter !== '' && $effectiveFilter !== 'none'
            && ! $apiKeys->contains('id', $effectiveFilter)) {
            $effectiveFilter = '';
        }

        $links = $base
            ->with(['domain', 'user', 'apiKey:id,name,key_prefix'])
            ->when($this->scope === 'public', function ($query) {
                $query->whereHas('domain', fn ($q) => $q->whereIn('hostname', DomainUrls::publicDashboardHostnames()));
            })
            ->when($this->scope === 'personal', function ($query) {
                $query->where(function ($q) {
                    $q->whereDoesntHave('domain')
                        ->orWhereHas('domain', fn ($qq) => $qq->whereNotIn('hostname', DomainUrls::publicDashboardHostnames()));
                });
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('slug', 'like', "%{$this->search}%")
                        ->orWhere('destination_url', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhere('tags', 'like', "%{$this->search}%");
                });
            })
            ->when($this->tag, fn ($query) => $query->where('tags', 'like', '%"'.strtolower($this->tag).'"%'))
            ->when(true, function ($query) use ($effectiveFilter) {
                if ($effectiveFilter === 'none') {
                    $query->whereNull('api_key_id');
                } elseif ($effectiveFilter !== '') {
                    $query->where('api_key_id', $effectiveFilter);
                } else {
                    // Default view: hide links whose key opted out of
                    // the dashboard (they live on their per-key page).
                    $query->visibleOnDashboard();
                }
            })
            ->orderBy($sortBy, $sortDir)
            ->paginate(20);

        $visibleColumns = $this->visibleColumns();

        // Preserve the back-link context (per-key page) on row links.
        $fromApiKey = $this->lockedApiKeyId ?? ($effectiveFilter !== '' && $effectiveFilter !== 'none' ? $effectiveFilter : null);

        $this->pageIds = $links->getCollection()->pluck('id')->all();

        return view('livewire.dashboard.link-table', [
            'links' => $links,
            'availableTags' => $this->availableTags($base),
            'apiKeys' => $apiKeys,
            'fromApiKey' => $fromApiKey,
            'availableColumns' => $this->availableColumns(),
            'visibleColumns' => $visibleColumns,
            'columnCount' => count($visibleColumns) + 2, // + checkbox + fixed Actions column
        ]);
    }

    /**
     * Distinct tags across the visible scope for the filter dropdown.
     * Tags are normalized lowercase at write time; matching is by
     * quoted element so 'doc' never matches 'docs'.
     *
     * @return list<string>
     */
    private function availableTags($base): array
    {
        $tags = (clone $base)
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->filter(fn ($tag) => is_string($tag) && $tag !== '')
            ->unique()
            ->sort()
            ->values()
            ->take(50)
            ->all();

        return array_values($tags);
    }
}
