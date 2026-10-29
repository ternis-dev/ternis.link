<?php

namespace App\Livewire\Dashboard;

use App\Livewire\Concerns\WithTableColumns;
use App\Models\ActivityLog;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Activity feed table (dash.ternis.link/activity).
 *
 * Lives as a Livewire component so the columns can be toggled and
 * reordered per user, like every other table on the dashboard.
 */
class ActivityTable extends Component
{
    use WithPagination;
    use WithTableColumns;

    public string $search = '';

    public string $sourceFilter = 'all';

    public string $keyFilter = 'all';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSourceFilter(): void
    {
        $this->resetPage();
    }

    public function updatingKeyFilter(): void
    {
        $this->resetPage();
    }

    protected function tableKey(): string
    {
        return 'dashboard.activity';
    }

    protected function availableColumns(): array
    {
        return [
            'when' => 'When',
            'action' => 'Action',
            'subject' => 'Subject',
            'by' => 'By',
        ];
    }

    public function render()
    {
        $user = auth()->user();

        $entries = ActivityLog::visibleTo($user->id)
            ->with(['actor', 'subjectOwner'])
            ->when($this->sourceFilter === 'web', function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNull('metadata->via')
                        ->orWhere('metadata->via', '!=', 'api');
                })->whereNull('metadata->api_key_id')
                    ->whereNull('metadata->auth_via');
            })
            ->when($this->sourceFilter === 'api', function ($q) {
                $q->where(function ($sub) {
                    $sub->where('metadata->via', 'api')
                        ->orWhereNotNull('metadata->api_key_id')
                        ->orWhere('metadata->auth_via', 'api-key')
                        ->orWhere('metadata->auth_via', 'session-token');
                });
            })
            ->when($this->keyFilter !== 'all', function ($q) {
                $q->where('metadata->api_key_id', $this->keyFilter);
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('action', 'like', "%{$this->search}%")
                        ->orWhere('subject_label', 'like', "%{$this->search}%")
                        ->orWhere('metadata->api_key_name', 'like', "%{$this->search}%")
                        ->orWhere('metadata->api_key_prefix', 'like', "%{$this->search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(25);

        $visibleColumns = $this->visibleColumns();

        $apiKeys = $user->apiKeys()->select('id', 'name', 'key_prefix')->get();

        return view('livewire.dashboard.activity-table', [
            'entries' => $entries,
            'availableColumns' => $this->availableColumns(),
            'visibleColumns' => $visibleColumns,
            'columnCount' => count($visibleColumns),
            'apiKeys' => $apiKeys,
        ]);
    }
}
