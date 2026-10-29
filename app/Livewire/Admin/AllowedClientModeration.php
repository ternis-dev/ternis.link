<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithTableColumns;
use App\Models\ActivityLog;
use App\Models\AllowedClient;
use App\Support\Activity;
use Livewire\Component;
use Livewire\WithPagination;

class AllowedClientModeration extends Component
{
    use WithPagination;
    use WithTableColumns;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $typeFilter = 'all';

    public bool $showModal = false;

    public ?string $editingId = null;

    public string $name = '';

    public string $ip_address = '';

    public string $user_agent = '';

    public string $notes = '';

    public bool $is_active = true;

    protected function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:120'],
            'ip_address' => ['nullable', 'string', 'max:255'],
            'user_agent' => ['nullable', 'string', 'max:512'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEditModal(string $id): void
    {
        $this->ensureAdmin();

        $client = AllowedClient::findOrFail($id);
        $this->editingId = $client->id;
        $this->name = (string) ($client->name ?? '');
        $this->ip_address = (string) ($client->ip_address ?? '');
        $this->user_agent = (string) ($client->user_agent ?? '');
        $this->notes = (string) ($client->notes ?? '');
        $this->is_active = (bool) $client->is_active;

        $this->resetValidation();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->ip_address = '';
        $this->user_agent = '';
        $this->notes = '';
        $this->is_active = true;
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->ensureAdmin();

        $this->validate();

        $ip = trim($this->ip_address);
        $ua = trim($this->user_agent);

        if ($ip === '' && $ua === '') {
            $this->addError('ip_address', 'Please provide an IP address, a User-Agent, or both (allowed pair).');
            $this->addError('user_agent', 'Please provide an IP address, a User-Agent, or both (allowed pair).');

            return;
        }

        $payload = [
            'name' => trim($this->name) !== '' ? trim($this->name) : null,
            'ip_address' => $ip !== '' ? $ip : null,
            'user_agent' => $ua !== '' ? $ua : null,
            'notes' => trim($this->notes) !== '' ? trim($this->notes) : null,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            $client = AllowedClient::findOrFail($this->editingId);
            $client->update($payload);

            Activity::record(ActivityLog::ADMIN_ALLOWED_CLIENT_UPDATED, auth()->user(), $client, [
                'ip_address' => $client->ip_address,
                'user_agent' => $client->user_agent,
            ]);

            $this->dispatch('notify', message: 'Allowed pair updated successfully.', type: 'success');
        } else {
            $payload['created_by'] = auth()->id();
            $client = AllowedClient::create($payload);

            Activity::record(ActivityLog::ADMIN_ALLOWED_CLIENT_CREATED, auth()->user(), $client, [
                'ip_address' => $client->ip_address,
                'user_agent' => $client->user_agent,
            ]);

            $this->dispatch('notify', message: 'Allowed pair created successfully.', type: 'success');
        }

        $this->closeModal();
    }

    public function toggleActive(string $id): void
    {
        $this->ensureAdmin();

        $client = AllowedClient::findOrFail($id);
        $client->update(['is_active' => ! $client->is_active]);

        Activity::record(ActivityLog::ADMIN_ALLOWED_CLIENT_UPDATED, auth()->user(), $client, [
            'is_active' => $client->is_active,
        ]);

        $status = $client->is_active ? 'activated' : 'deactivated';
        $this->dispatch('notify', message: "Rule has been {$status}.", type: 'info');
    }

    public function delete(string $id): void
    {
        $this->ensureAdmin();

        $client = AllowedClient::findOrFail($id);
        $client->delete();

        Activity::record(ActivityLog::ADMIN_ALLOWED_CLIENT_DELETED, auth()->user(), null, [
            'deleted_id' => $id,
            'name' => $client->name,
            'ip_address' => $client->ip_address,
            'user_agent' => $client->user_agent,
        ]);

        $this->dispatch('notify', message: 'Allowed pair deleted.', type: 'info');
    }

    public function render()
    {
        $this->ensureAdmin();

        $entries = AllowedClient::with('creator')
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($this->typeFilter === 'pair', fn ($q) => $q->whereNotNull('ip_address')->whereNotNull('user_agent'))
            ->when($this->typeFilter === 'ip', fn ($q) => $q->whereNotNull('ip_address')->whereNull('user_agent'))
            ->when($this->typeFilter === 'ua', fn ($q) => $q->whereNull('ip_address')->whereNotNull('user_agent'))
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('ip_address', 'like', "%{$this->search}%")
                        ->orWhere('user_agent', 'like', "%{$this->search}%")
                        ->orWhere('notes', 'like', "%{$this->search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.admin.allowed-client-moderation', [
            'entries' => $entries,
            'visibleColumns' => $visible = $this->visibleColumns(),
            'columnCount' => count($visible) + 1,
        ]);
    }

    protected function tableKey(): string
    {
        return 'admin.allowed_clients';
    }

    protected function availableColumns(): array
    {
        return [
            'name' => 'Name',
            'ip_address' => 'IP Address',
            'user_agent' => 'User Agent',
            'rule_type' => 'Rule Type',
            'status' => 'Status',
            'created_by' => 'Added By',
            'created_at' => 'Added',
        ];
    }

    private function ensureAdmin(): void
    {
        if (! auth()->check() || ! auth()->user()->isAdmin()) {
            abort(403, 'Admin access required.');
        }
    }
}
