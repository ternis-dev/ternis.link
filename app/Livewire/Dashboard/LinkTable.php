<?php

namespace App\Livewire\Dashboard;

use App\Models\Link;
use Livewire\Component;
use Livewire\WithPagination;

class LinkTable extends Component
{
    use WithPagination;

    public string $search = '';

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
                        ->orWhere('destination_url', 'like', "%{$this->search}%");
                    if ($isAdmin) {
                        $q->orWhereHas('user', fn ($u) => $u
                            ->where('email', 'like', "%{$this->search}%")
                            ->orWhere('name', 'like', "%{$this->search}%"));
                    }
                });
            })
            ->orderBy($sortBy, $sortDir)
            ->paginate(20);

        return view('livewire.dashboard.link-table', compact('links'));
    }
}
