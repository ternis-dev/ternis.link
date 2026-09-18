<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use Livewire\WithPagination;

class LinkTable extends Component
{
    use WithPagination;

    public string $search = '';

    public string $sortBy = 'created_at';

    public string $sortDir = 'desc';

    public function updatingSearch(): void
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

    public function deactivate(int $linkId): void
    {
        $link = auth()->user()->links()->findOrFail($linkId);
        $link->update(['is_active' => false]);
    }

    public function render()
    {
        $links = auth()->user()
            ->links()
            ->with('domain')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('slug', 'like', "%{$this->search}%")
                        ->orWhere('destination_url', 'like', "%{$this->search}%");
                });
            })
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate(20);

        return view('livewire.dashboard.link-table', compact('links'));
    }
}
