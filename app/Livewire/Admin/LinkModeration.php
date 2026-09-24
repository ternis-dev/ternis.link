<?php

namespace App\Livewire\Admin;

use App\Models\Link;
use Livewire\Component;
use Livewire\WithPagination;

class LinkModeration extends Component
{
    use WithPagination;

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
    }

    public function reactivate(string $linkId): void
    {
        $this->ensureAdmin();

        $link = Link::findOrFail($linkId);
        $link->update(['is_active' => true]);
    }

    public function render()
    {
        $this->ensureAdmin();

        $links = Link::with(['domain', 'user'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('slug', 'like', "%{$this->search}%")
                        ->orWhere('destination_url', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status === 'active', fn ($q) => $q->where('is_active', true)->where(fn ($s) => $s->whereNull('expires_at')->orWhere('expires_at', '>', now())))
            ->when($this->status === 'disabled', fn ($q) => $q->where('is_active', false))
            ->when($this->status === 'expired', fn ($q) => $q->whereNotNull('expires_at')->where('expires_at', '<=', now()))
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate(20);

        return view('livewire.admin.link-moderation', compact('links'));
    }

    private function ensureAdmin(): void
    {
        if (! auth()->check() || ! auth()->user()->isAdmin()) {
            abort(403, 'Admin access required.');
        }
    }
}
