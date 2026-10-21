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
        $entries = ActivityLog::visibleTo(auth()->user()->id)
            ->with(['actor', 'subjectOwner'])
            ->orderByDesc('created_at')
            ->paginate(25);

        $visibleColumns = $this->visibleColumns();

        return view('livewire.dashboard.activity-table', [
            'entries' => $entries,
            'availableColumns' => $this->availableColumns(),
            'visibleColumns' => $visibleColumns,
            'columnCount' => count($visibleColumns),
        ]);
    }
}
