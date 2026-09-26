<?php

namespace App\Livewire\Dashboard;

use App\Livewire\Concerns\WithTableColumns;
use App\Models\ActivityLog;
use App\Support\Activity;
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

    public string $sortBy = 'created_at';

    public string $sortDir = 'desc';

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
        $link = auth()->user()->links()->findOrFail($linkId);
        $link->update(['is_active' => false]);

        Activity::record(ActivityLog::LINK_DEACTIVATED, auth()->user(), $link, [
            'slug' => $link->slug,
        ]);
    }

    public function render()
    {
        $sortBy = in_array($this->sortBy, self::SORTABLE, true) ? $this->sortBy : 'created_at';
        $sortDir = $this->sortDir === 'asc' ? 'asc' : 'desc';

        $base = auth()->user()->links();

        $links = $base
            ->with(['domain', 'user'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('slug', 'like', "%{$this->search}%")
                        ->orWhere('destination_url', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhere('tags', 'like', "%{$this->search}%");
                });
            })
            ->when($this->tag, fn ($query) => $query->where('tags', 'like', '%"'.strtolower($this->tag).'"%'))
            ->orderBy($sortBy, $sortDir)
            ->paginate(20);

        $visibleColumns = $this->visibleColumns();

        return view('livewire.dashboard.link-table', [
            'links' => $links,
            'availableTags' => $this->availableTags($base),
            'availableColumns' => $this->availableColumns(),
            'visibleColumns' => $visibleColumns,
            'columnCount' => count($visibleColumns) + 1, // + fixed Actions column
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
