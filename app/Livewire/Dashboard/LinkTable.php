<?php

namespace App\Livewire\Dashboard;

use App\Livewire\Concerns\WithTableColumns;
use App\Models\ActivityLog;
use App\Models\Domain;
use App\Models\Link;
use App\Services\LinkService;
use App\Support\Activity;
use App\Support\DomainUrls;
use Illuminate\Support\Collection;
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

    /**
     * Include links made with keys that opted out of the dashboard
     * (`show_on_dashboard = false`). Off by default; opt-in via the
     * "Include hidden" checkbox next to the search.
     */
    public bool $includeHidden = false;

    public string $sortBy = 'created_at';

    public string $sortDir = 'desc';

    /**
     * Sort option selector: newest, oldest, most_clicks, least_clicks, slug_asc, slug_desc.
     */
    public string $sortOption = 'newest';

    /**
     * Status filter: all, active, disabled, expired, password.
     */
    public string $statusFilter = 'all';

    /**
     * Clicks traffic filter: all, has_clicks, 100_plus, 10_plus, zero.
     */
    public string $clicksFilter = 'all';

    /**
     * Admin-only scope: 'mine' (strictly own links) or 'all' (all platform links).
     */
    public string $adminScope = 'mine';

    /**
     * Dashboard scope: null = legacy global (all own links),
     * 'public' = only my.ternis.link hostnames (href.nz, meinlink.at,
     * href.yt, qr.href.nz), 'personal' = everything else (dash side of
     * the split: clicked.at, ternis.link, href.re, partner, custom).
     */
    public ?string $scope = null;

    /**
     * Presentation theme: 'dashboard' (neutral) or 'public' (my.ternis.link
     * fresh-minimal theme + public-dashboard route names). Logic is
     * identical; only route prefixing and styling hooks change.
     */
    public string $theme = 'dashboard';

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
        $cols = [
            'slug' => 'Short Link',
            'destination' => 'Destination URL',
            'domain' => 'Domain',
        ];

        if (auth()->check() && auth()->user()->isAdmin() && $this->adminScope === 'all') {
            $cols['owner'] = 'Owner';
        }

        $cols['clicks'] = 'Clicks';
        $cols['status'] = 'Status';
        $cols['created'] = 'Created';

        return $cols;
    }

    /**
     * Domain filter: '' = all hosts, otherwise hostname.
     */
    public string $domainFilter = '';

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

    public function updatingDomainFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingClicksFilter(): void
    {
        $this->resetPage();
    }

    public function updatingAdminScope(): void
    {
        $this->resetPage();
    }

    public function updatingIncludeHidden(): void
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
        $this->tag = '';
        $this->domainFilter = '';
        $this->statusFilter = 'all';
        $this->clicksFilter = 'all';
        $this->sortOption = 'newest';
        $this->sortBy = 'created_at';
        $this->sortDir = 'desc';
        $this->includeHidden = false;
        $this->adminScope = 'mine';
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

        $this->syncSortOption();
    }

    private function syncSortOption(): void
    {
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
        $user = auth()->user();
        $link = ($user && $user->isAdmin() && $this->adminScope === 'all')
            ? Link::notRemoved()->findOrFail($linkId)
            : $user->links()->notRemoved()->findOrFail($linkId);

        app(LinkService::class)->deactivate($link);

        Activity::record(ActivityLog::LINK_DEACTIVATED, $user, $link, [
            'slug' => $link->slug,
            'via' => 'dashboard',
        ]);

        $this->dispatch('notify', message: "Deactivated {$link->slug}.", type: 'info');
    }

    public function toggleSelectAll(bool $select): void
    {
        $this->selected = $select ? $this->pageIds : [];
    }

    public function bulkSetActive(LinkService $links, bool $active): void
    {
        $user = auth()->user();
        $isAdmin = $user && $user->isAdmin() && $this->adminScope === 'all';
        $ids = array_slice(array_unique($this->selected), 0, 200);
        $done = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            $link = $isAdmin ? Link::notRemoved()->find($id) : $user->links()->notRemoved()->find($id);

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

        if ($done > 0) {
            $this->dispatch('notify', message: $this->bulkNotice, type: 'success');
        }
    }

    public function render()
    {
        $sortBy = in_array($this->sortBy, self::SORTABLE, true) ? $this->sortBy : 'created_at';
        $sortDir = $this->sortDir === 'asc' ? 'asc' : 'desc';

        $user = auth()->user();
        $isAdmin = $user && $user->isAdmin();
        $isAllPlatform = $isAdmin && $this->adminScope === 'all';

        $base = $isAllPlatform ? Link::notRemoved() : $user->links()->notRemoved();

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

        $links = (clone $base)
            ->with(['domain', 'user', 'apiKey:id,name,key_prefix'])
            ->when($this->scope === 'public', function ($query) {
                $query->whereHas('domain', fn ($q) => $q->whereIn('hostname', DomainUrls::publicDashboardHostnames()));
            })
            ->when($this->domainFilter !== '' && ($this->scope !== 'public' || in_array($this->domainFilter, DomainUrls::publicDashboardHostnames(), true)), function ($query) {
                $query->whereHas('domain', fn ($q) => $q->where('hostname', $this->domainFilter));
            })
            ->when($this->search, function ($query) use ($isAllPlatform) {
                $query->where(function ($q) use ($isAllPlatform) {
                    $q->where('slug', 'like', "%{$this->search}%")
                        ->orWhere('destination_url', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhere('tags', 'like', "%{$this->search}%");

                    if ($isAllPlatform) {
                        $q->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$this->search}%")->orWhere('name', 'like', "%{$this->search}%"));
                    }
                });
            })
            ->when($this->tag, fn ($query) => $query->where('tags', 'like', '%"'.strtolower($this->tag).'"%'))
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true)->where(fn ($s) => $s->whereNull('expires_at')->orWhere('expires_at', '>', now())))
            ->when($this->statusFilter === 'disabled', fn ($q) => $q->where('is_active', false))
            ->when($this->statusFilter === 'expired', fn ($q) => $q->whereNotNull('expires_at')->where('expires_at', '<=', now()))
            ->when($this->statusFilter === 'password', fn ($q) => $q->whereNotNull('password_hash'))
            ->when($this->clicksFilter === 'has_clicks', fn ($q) => $q->where('click_count', '>', 0))
            ->when($this->clicksFilter === '100_plus', fn ($q) => $q->where('click_count', '>=', 100))
            ->when($this->clicksFilter === '10_plus', fn ($q) => $q->where('click_count', '>=', 10))
            ->when($this->clicksFilter === 'zero', fn ($q) => $q->where('click_count', 0))
            ->when(! $isAllPlatform, function ($query) use ($effectiveFilter) {
                if ($effectiveFilter === 'none') {
                    $query->whereNull('api_key_id');
                } elseif ($effectiveFilter !== '') {
                    $query->where('api_key_id', $effectiveFilter);
                } elseif (! $this->includeHidden) {
                    // Default view: hide links whose key opted out of
                    // the dashboard (they live on their per-key page) —
                    // unless the "Include hidden" checkbox is set.
                    $query->visibleOnDashboard();
                }
            })
            ->orderBy($sortBy, $sortDir)
            ->paginate(20);

        $visibleColumns = $this->visibleColumns();

        // How many links in this scope exist but are kept off the
        // dashboard by their API key. Without this the table says
        // "create your first link" while the overview counts them.
        $hiddenByKeyCount = $isAllPlatform ? 0 : $this->hiddenByKeyCount($base, $effectiveFilter);

        // Preserve the back-link context (per-key page) on row links.
        $fromApiKey = $this->lockedApiKeyId ?? ($effectiveFilter !== '' && $effectiveFilter !== 'none' ? $effectiveFilter : null);

        $this->pageIds = $links->getCollection()->pluck('id')->all();

        $availableDomains = $this->availableDomains($user, $isAllPlatform);

        return view('livewire.dashboard.link-table', [
            'links' => $links,
            'availableTags' => $this->availableTags($this->scopedBase($base, $effectiveFilter)),
            'availableDomains' => $availableDomains,
            'apiKeys' => $apiKeys,
            'apiKeyLinkCounts' => $this->apiKeyLinkCounts($base),
            'fromApiKey' => $fromApiKey,
            'visibleColumns' => $visibleColumns,
            'hiddenByKeyCount' => $hiddenByKeyCount,
            'columnCount' => count($visibleColumns) + 2, // + checkbox + fixed Actions column
            'isAllPlatform' => $isAllPlatform,
            'isAdmin' => $isAdmin,
        ]);
    }

    private function availableDomains($user, bool $isAllPlatform): array
    {
        if ($isAllPlatform) {
            return Domain::orderBy('hostname')->pluck('hostname')->all();
        }

        if ($this->scope === 'public') {
            return DomainUrls::publicDashboardHostnames();
        }

        return $user->links()->with('domain')->get()
            ->pluck('domain.hostname')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * How many links each owned API key has in the current scope.
     *
     * The origin dropdown lists every key, so a key whose links all
     * live on the other dashboard would silently empty the table —
     * this lets the dropdown say so.
     *
     * @return Collection<int, int>
     */
    private function apiKeyLinkCounts(mixed $base): Collection
    {
        return $this->scopedBase($base, '')
            ->reorder()
            ->whereNotNull('api_key_id')
            ->selectRaw('api_key_id, count(*) as link_count')
            ->groupBy('api_key_id')
            ->pluck('link_count', 'api_key_id')
            ->map(fn ($count) => (int) $count);
    }

    /**
     * The user's links narrowed to the current scope (host partition,
     * domain filter, API-key filter) — without the search and without
     * the dashboard-visibility filter.
     *
     * The tag dropdown and the hidden-link count are derived from it,
     * so they only ever mention links that can actually show up here.
     */
    private function scopedBase(mixed $base, string $effectiveFilter): mixed
    {
        return (clone $base)
            ->when($this->scope === 'public', function ($query) {
                $query->whereHas('domain', fn ($q) => $q->whereIn('hostname', DomainUrls::publicDashboardHostnames()));
            })
            ->when($this->domainFilter !== '' && ($this->scope !== 'public' || in_array($this->domainFilter, DomainUrls::publicDashboardHostnames(), true)), function ($query) {
                $query->whereHas('domain', fn ($q) => $q->where('hostname', $this->domainFilter));
            })
            ->when($effectiveFilter === 'none', function ($query) {
                $query->whereNull('api_key_id');
            })
            ->when($effectiveFilter !== '' && $effectiveFilter !== 'none', function ($query) use ($effectiveFilter) {
                $query->where('api_key_id', $effectiveFilter);
            });
    }

    /**
     * Links in the current scope whose API key keeps them off the
     * dashboard (show_on_dashboard = false).
     */
    private function hiddenByKeyCount(mixed $base, string $effectiveFilter): int
    {
        return (clone $base)
            ->when($this->scope === 'public', function ($query) {
                $query->whereHas('domain', fn ($q) => $q->whereIn('hostname', DomainUrls::publicDashboardHostnames()));
            })
            ->when($this->domainFilter !== '' && ($this->scope !== 'public' || in_array($this->domainFilter, DomainUrls::publicDashboardHostnames(), true)), function ($query) {
                $query->whereHas('domain', fn ($q) => $q->where('hostname', $this->domainFilter));
            })
            ->when($effectiveFilter === 'none', function ($query) {
                $query->whereNull('api_key_id');
            })
            ->when($effectiveFilter !== '' && $effectiveFilter !== 'none', function ($query) use ($effectiveFilter) {
                $query->where('api_key_id', $effectiveFilter);
            })
            ->reorder()
            ->whereNotNull('api_key_id')
            ->whereHas('apiKey', fn ($q) => $q->where('show_on_dashboard', false))
            ->count();
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
