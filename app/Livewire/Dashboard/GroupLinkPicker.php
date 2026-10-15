<?php

namespace App\Livewire\Dashboard;

use App\Support\Activity;
use Livewire\Component;

class GroupLinkPicker extends Component
{
    public string $groupId = '';

    public string $search = '';

    public array $selected = [];

    public function mount(string $groupId): void
    {
        $this->groupId = $groupId;
    }

    public function add(): void
    {
        if (empty($this->selected)) {
            return;
        }

        $user = auth()->user();
        $group = $user->groups()->notRemoved()->findOrFail($this->groupId);

        $owned = $user->links()->notRemoved()->whereIn('id', $this->selected)->pluck('id')->toArray();

        if (empty($owned)) {
            $this->dispatch('notify', message: 'No valid links selected.', type: 'error');

            return;
        }

        $existing = $group->links()->pluck('links.id')->toArray();
        $next = (int) ($group->items()->max('sort_order') ?? -1) + 1;

        foreach (array_diff($owned, $existing) as $linkId) {
            $group->items()->create(['link_id' => $linkId, 'sort_order' => $next++]);
        }

        Activity::record('group.links_added', $user, $group, ['count' => count($owned)]);

        $this->selected = [];
        $this->search = '';
        $this->dispatch('notify', message: count($owned).' link(s) added.', type: 'success');

        $this->redirect(request()->header('Referer', route('dashboard.groups.show', $this->groupId)), navigate: true);
    }

    public function render()
    {
        $user = auth()->user();
        $group = $user->groups()->notRemoved()->findOrFail($this->groupId);
        $memberIds = $group->links()->pluck('links.id')->toArray();

        $results = $user->links()->notRemoved()->with(['domain'])
            ->when($this->search !== '', fn ($q) => $q->where(function ($qq) {
                $qq->where('slug', 'like', '%'.$this->search.'%')
                    ->orWhere('destination_url', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%');
            }))
            ->whereNotIn('links.id', $memberIds)
            ->orderByDesc('links.created_at')
            ->limit(10)
            ->get();

        return view('livewire.dashboard.group-link-picker', [
            'results' => $results,
        ]);
    }
}
