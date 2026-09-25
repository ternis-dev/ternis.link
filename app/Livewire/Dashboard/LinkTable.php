<?php

namespace App\Livewire\Dashboard;

use App\Models\ActivityLog;
use App\Models\Link;
use App\Support\Activity;
use App\Support\DomainUrls;
use App\Support\Notifier;
use Livewire\Component;
use Livewire\WithPagination;

class LinkTable extends Component
{
    use WithPagination;

    /**
     * Refresh when a link is created through the quick-create modal
     * rendered on the same page.
     */
    protected $listeners = ['link-created' => '$refresh'];

    public string $search = '';

    public string $tag = '';

    public string $sortBy = 'created_at';

    public string $sortDir = 'desc';

    /**
     * Columns allowed for sorting (prevents arbitrary orderBy injection
     * via the public Livewire properties).
     */
    private const SORTABLE = ['slug', 'click_count', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTag(): void
    {
        $this->resetPage();
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
        // Admins may deactivate ANY link; regular users only their own.
        $link = auth()->user()->isAdmin()
            ? Link::findOrFail($linkId)
            : auth()->user()->links()->findOrFail($linkId);
        $link->update(['is_active' => false]);

        Activity::record(ActivityLog::LINK_DEACTIVATED, auth()->user(), $link, [
            'slug' => $link->slug,
        ]);

        // An admin deactivating someone else's link from here owes the
        // owner an explanation in their inbox.
        if ($link->user_id !== null && $link->user_id !== auth()->id()) {
            $owner = $link->user;
            if ($owner) {
                Notifier::security(
                    $owner,
                    'Your link was deactivated',
                    ["The link {$link->slug} was deactivated by an administrator."],
                    DomainUrls::dashboard('/links'),
                    'View your links',
                );
            }
        }
    }

    public function render()
    {
        $isAdmin = auth()->check() && auth()->user()->isAdmin();
        $sortBy = in_array($this->sortBy, self::SORTABLE, true) ? $this->sortBy : 'created_at';
        $sortDir = $this->sortDir === 'asc' ? 'asc' : 'desc';

        $base = $isAdmin
            ? Link::query()
            : auth()->user()->links();

        $links = $base
            ->with(['domain', 'user'])
            ->when($this->search, function ($query) use ($isAdmin) {
                $query->where(function ($q) use ($isAdmin) {
                    $q->where('slug', 'like', "%{$this->search}%")
                        ->orWhere('destination_url', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhere('tags', 'like', "%{$this->search}%");
                    if ($isAdmin) {
                        $q->orWhereHas('user', fn ($u) => $u
                            ->where('email', 'like', "%{$this->search}%")
                            ->orWhere('name', 'like', "%{$this->search}%"));
                    }
                });
            })
            ->when($this->tag, fn ($query) => $query->where('tags', 'like', '%"'.strtolower($this->tag).'"%'))
            ->orderBy($sortBy, $sortDir)
            ->paginate(20);

        return view('livewire.dashboard.link-table', [
            'links' => $links,
            'availableTags' => $this->availableTags($base),
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
