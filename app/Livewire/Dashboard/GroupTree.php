<?php

namespace App\Livewire\Dashboard;

use App\Models\Click;
use App\Models\LinkGroup;
use App\Support\Activity;
use App\Support\DomainUrls;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Livewire\Component;

class GroupTree extends Component
{
    /**
     * Which links count towards a group's stats: 'all' (dash) or
     * 'public' (the public dashboard only groups its shortener links).
     */
    public string $scope = 'all';

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
            $group->setAttribute('computed_links', count($this->scopedLinkIds($group)));
            $group->setAttribute('computed_clicks', $this->scopedClicks($group));
            $group->children->each(function (LinkGroup $child) {
                $child->setAttribute('computed_links', count($this->scopedLinkIds($child)));
                $child->setAttribute('computed_clicks', $this->scopedClicks($child));
            });
        });

        return view('livewire.dashboard.group-tree', [
            'groups' => $groups,
        ]);
    }

    /**
     * The group's link ids, narrowed to the links of this surface.
     *
     * @return list<string>
     */
    private function scopedLinkIds(LinkGroup $group): array
    {
        $ids = $group->allLinkIds();

        if ($this->scope !== 'public') {
            return $ids;
        }

        return $this->publicLinksQuery()->whereIn('links.id', $ids)->pluck('links.id')->all();
    }

    /**
     * Clicks of the group's links, narrowed to this surface.
     */
    private function scopedClicks(LinkGroup $group): int
    {
        $ids = $this->scopedLinkIds($group);

        if ($ids === []) {
            return 0;
        }

        return Click::whereIn('link_id', $ids)
            ->where('is_direct_url', false)
            ->sum('click_count');
    }

    /**
     * The user's public shortener links.
     */
    private function publicLinksQuery(): HasMany|Builder
    {
        return auth()->user()->links()->notRemoved()
            ->whereHas('domain', fn (Builder $q) => $q->whereIn('hostname', DomainUrls::publicDashboardHostnames()));
    }
}
