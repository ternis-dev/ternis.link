<?php

namespace App\Livewire\Admin;

use App\Models\Domain;
use Livewire\Component;
use Livewire\WithPagination;

class DomainModeration extends Component
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

    /**
     * Deactivate a user-owned domain (abuse containment). Links and
     * analytics are preserved. System domains are protected: tampered
     * IDs resolve to null and become a no-op with an error.
     */
    public function deactivate(int $domainId): void
    {
        $this->ensureAdmin();

        $domain = $this->moderatableDomain($domainId);

        if (! $domain) {
            $this->addError('domain', 'Domain not found or protected.');

            return;
        }

        $domain->update(['is_active' => false]);
    }

    public function reactivate(int $domainId): void
    {
        $this->ensureAdmin();

        $domain = $this->moderatableDomain($domainId);

        if (! $domain) {
            $this->addError('domain', 'Domain not found or protected.');

            return;
        }

        $domain->update(['is_active' => true]);
    }

    public function render()
    {
        $this->ensureAdmin();

        $domains = Domain::with(['user'])->withCount('links')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('hostname', 'like', "%{$this->search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->status === 'system', fn ($q) => $q->whereNull('user_id'))
            ->when($this->status === 'verified', fn ($q) => $q->whereNotNull('user_id')->whereNotNull('verified_at')->where('is_active', true))
            ->when($this->status === 'pending', fn ($q) => $q->whereNotNull('user_id')->whereNull('verified_at')->where('is_active', true))
            ->when($this->status === 'disabled', fn ($q) => $q->whereNotNull('user_id')->where('is_active', false))
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate(20);

        return view('livewire.admin.domain-moderation', compact('domains'));
    }

    private function ensureAdmin(): void
    {
        if (! auth()->check() || ! auth()->user()->isAdmin()) {
            abort(403, 'Admin access required.');
        }
    }

    /**
     * Only user-owned domains can be moderated; system hostnames and
     * tampered IDs resolve to null.
     */
    private function moderatableDomain(int $domainId): ?Domain
    {
        return Domain::where('id', $domainId)->whereNotNull('user_id')->first();
    }
}
