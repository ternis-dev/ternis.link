<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithTableColumns;
use App\Models\ActivityLog;
use App\Models\AllowedClient;
use App\Models\ErrorEncounter;
use App\Support\Activity;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class ErrorEncounterTable extends Component
{
    use WithPagination;
    use WithTableColumns;

    public string $search = '';

    public string $codeFilter = 'all';

    public function updatingSearch(): void\n
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

    public function allowClient(string $encounterId): void
    {
        $this->ensureAdmin();

        $encounter = ErrorEncounter::findOrFail($encounterId);
        if (empty($encounter->user_agent)) {
            $this->dispatch('notify', message: 'No User-Agent associated with this error encounter.', type: 'error');

            return;
        }

        $ua = trim($encounter->user_agent);

        $existing = AllowedClient::where('user_agent', $ua)
            ->whereNull('ip_address')
            ->first();

        if ($existing) {
            $this->dispatch('notify', message: "Rule for User-Agent '{$ua}' already exists.", type: 'info');

            return;
        }

        $client = AllowedClient::create([
            'name' => 'From error: '.Str::limit($ua, 40),
            'user_agent' => $ua,
            'created_by' => auth()->id(),
            'is_active' => true,
            'notes' => "Added from error encounter #{$encounter->id} ({$encounter->method} {$encounter->path})",
        ]);

        Activity::record(ActivityLog::ADMIN_ALLOWED_CLIENT_CREATED, auth()->user(), $client, [
            'user_agent' => $ua,
            'source' => 'error_encounter',
        ]);

        $this->dispatch('notify', message: "Created allowed rule for User-Agent '{$ua}'.", type: 'success');
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
            'user_agent' => 'User Agent',
        ];
    }

    private function ensureAdmin(): void
    {
        if (! auth()->check() || ! auth()->user()->isAdmin()) {
            abort(403, 'Admin access required.');
        }
    }
}
