<?php

namespace App\Livewire\Dashboard;

use App\Models\LinkGroup;
use App\Support\Activity;
use Livewire\Component;

class GroupTree extends Component
{
    public string $search = '';

    public ?string $expanded = null;

    protected $listeners = ['group-created' => '$refresh', 'group-updated' => '$refresh'];

    public function toggle(string $groupId): void
    {
        $this->expanded = $this->expanded === $groupId ? null : $groupId;
    }

    public function delete(string $groupId): void
    {
        $group = auth()->user()->groups()->notRemoved()->findOrFail($groupId);
        $group->children()->update(['parent_id' => $group->parent_id]);
        $group->update(['is_removed' => true]);

        Activity::record('group.deleted', auth()->user(), $group);

        $this->dispatch('notify', message: "Group \"{$group->name}\" deleted.", type: 'success');
    }

    public function render()
    {
        $base = auth()->user()->groups()->notRemoved()->with(['children' => fn ($q) => $q->notRemoved()->orderBy('sort_order')]);

        if ($this->search !== '') {
            $base->where('name', 'like', '%'.$this->search.'%');
        }

        $groups = $base->orderBy('sort_order')->get();

        // Attach computed stats (links incl. descendants, total clicks).
        $groups->each(function (LinkGroup $group) {
            $group->setAttribute('computed_links', count($group->allLinkIds()));
            $group->setAttribute('computed_clicks', $group->totalClicks());
            $group->children->each(function (LinkGroup $child) {
                $child->setAttribute('computed_links', count($child->allLinkIds()));
                $child->setAttribute('computed_clicks', $child->totalClicks());
            });
        });

        return view('livewire.dashboard.group-tree', [
            'groups' => $groups,
        ]);
    }
}
