<?php

namespace App\Livewire\Admin;

use App\Models\ActivityLog;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityLogTable extends Component
{
    use WithPagination;

    public string $search = '';

    public string $actionFilter = 'all';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingActionFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $this->ensureAdmin();

        $entries = ActivityLog::with(['actor', 'subjectOwner'])
            ->when($this->actionFilter !== 'all', fn ($q) => $q->withAction($this->actionFilter))
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('action', 'like', "%{$this->search}%")
                        ->orWhere('subject_label', 'like', "%{$this->search}%")
                        ->orWhereHas('actor', fn ($u) => $u
                            ->where('email', 'like', "%{$this->search}%")
                            ->orWhere('name', 'like', "%{$this->search}%"));
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.admin.activity-log-table', [
            'entries' => $entries,
            'actions' => ActivityLog::query()
                ->select('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action'),
        ]);
    }

    private function ensureAdmin(): void
    {
        if (! auth()->check() || ! auth()->user()->isAdmin()) {
            abort(403, 'Admin access required.');
        }
    }
}
