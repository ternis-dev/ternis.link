<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithTableColumns;
use App\Models\ErrorEncounter;
use Livewire\Component;
use Livewire\WithPagination;

class ErrorEncounterTable extends Component
{
    use WithPagination;
    use WithTableColumns;

    public string $search = '';

    public string $codeFilter = 'all';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCodeFilter(): void
    {
        $this->resetPage();
    }

    public function delete(string $encounterId): void
    {
        $this->ensureAdmin();

        ErrorEncounter::findOrFail($encounterId)->delete();
    }

    public function render()
    {
        $this->ensureAdmin();

        $entries = ErrorEncounter::with('user')
            ->when($this->codeFilter === '4xx', fn ($q) => $q->where('http_code', '>=', 400)->where('http_code', '<', 500))
            ->when($this->codeFilter === '5xx', fn ($q) => $q->where('http_code', '>=', 500))
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('error_message', 'like', "%{$this->search}%")
                        ->orWhere('exception_class', 'like', "%{$this->search}%")
                        ->orWhere('path', 'like', "%{$this->search}%")
                        ->orWhere('host', 'like', "%{$this->search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.admin.error-encounter-table', [
            'entries' => $entries,
            'availableColumns' => $this->availableColumns(),
            'visibleColumns' => $visible = $this->visibleColumns(),
            'columnCount' => count($visible) + 1, // + fixed Actions column
        ]);
    }

    protected function tableKey(): string
    {
        return 'admin.errors';
    }

    protected function availableColumns(): array
    {
        return [
            'when' => 'When',
            'code' => 'Code',
            'request' => 'Request',
            'message' => 'Error',
            'user' => 'User',
        ];
    }

    private function ensureAdmin(): void
    {
        if (! auth()->check() || ! auth()->user()->isAdmin()) {
            abort(403, 'Admin access required.');
        }
    }
}
