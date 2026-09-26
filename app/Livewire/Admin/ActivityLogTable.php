<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithTableColumns;
use App\Models\ActivityLog;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityLogTable extends Component
{
    use WithPagination;
    use WithTableColumns;

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
            'availableColumns' => $this->availableColumns(),
            'visibleColumns' => $visible = $this->visibleColumns(),
            'columnCount' => count($visible),
        ]);
    }

    protected function tableKey(): string
    {
        return 'admin.activity';
    }

    protected function availableColumns(): array
    {
        return [
            'when' => 'When',
            'action' => 'Action',
            'subject' => 'Subject',
            'actor' => 'Actor',
            'owner' => 'Owner',
        ];
    }

    private function ensureAdmin(): void
    {
        if (! auth()->check() || ! auth()->user()->isAdmin()) {
            abort(403, 'Admin access required.');
        }
    }
}
