<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Click;
use App\Models\LinkGroup;
use App\Support\Activity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;

/**
 * Link-group management, shared by the dash and the public dashboard.
 *
 * Both surfaces manage the SAME groups (a group is a group), but the
 * surface decides three things:
 *
 * - where the pages live (`groupViews()`),
 * - which route names the redirects use (`groupRoutePrefix()`),
 * - and which links may be put into a group (`groupLinksQuery()`).
 *
 * The public dashboard only ever manages the public shortener links,
 * so a link created on dash can never sneak into a public group.
 */
trait ManagesLinkGroups
{
    /**
     * View namespace of the group pages on this surface.
     */
    abstract protected function groupViews(): string;

    /**
     * Route name prefix of the group routes on this surface.
     */
    abstract protected function groupRoutePrefix(): string;

    /**
     * The links that may be grouped on this surface.
     *
     * @return HasMany|Builder
     */
    abstract protected function groupLinksQuery($user): object;

    /**
     * Scope of the group pages: 'all' (dash) or 'public' (the public
     * dashboard, which only groups its shortener links).
     */
    protected function groupScope(): string
    {
        return 'all';
    }

    public function groups()
    {
        return view($this->groupViews().'.index', ['groupScope' => $this->groupScope()]);
    }

    /**
     * Create group page.
     */
    public function createGroup(Request $request)
    {
        $parent = null;

        if ($request->filled('parent_id')) {
            $parent = auth()->user()->groups()->notRemoved()->findOrFail($request->query('parent_id'));
        }

        return view($this->groupViews().'.create', ['parent' => $parent, 'groupScope' => $this->groupScope()]);
    }

    /**
     * Store a new group (root or child when parent_id is given).
     */
    public function storeGroup(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'parent_id' => ['nullable', 'string'],
        ]);

        $parent = null;

        if (! empty($validated['parent_id'])) {
            $parent = $user->groups()->notRemoved()->findOrFail($validated['parent_id']);
        }

        $group = $user->groups()->create([
            'parent_id' => $parent?->id,
            'name' => trim($validated['name']),
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'] ?? null,
            'sort_order' => ($parent
                ? $parent->children()->max('sort_order') ?? -1
                : $user->groups()->root()->max('sort_order') ?? -1) + 1,
        ]);

        Activity::record('group.created', $user, $group);

        return redirect()->route($this->groupRoutePrefix().'groups.show', $group->id)
            ->with('success', "Group \"{$group->name}\" created.");
    }

    /**
     * Store a subgroup (same as storeGroup with forced parent).
     */
    public function storeSubgroup(Request $request, string $group)
    {
        $request->merge(['parent_id' => $group]);

        return $this->storeGroup($request);
    }

    /**
     * Group detail + combined analytics (own links, descendants
     * included). Strictly per-user.
     */
    public function showGroup(Request $request, string $group)
    {
        $user = auth()->user();
        $group = $user->groups()->notRemoved()->with(['parent', 'children'])->findOrFail($group);

        $period = (int) $request->query('period', 30);
        $period = in_array($period, [7, 30, 90], true) ? $period : 30;

        $linkIds = $group->allLinkIds();
        $directLinkIds = $group->links()->pluck('links.id')->toArray();

        $links = $this->groupLinksQuery($user)->with(['domain'])
            ->whereIn('links.id', $directLinkIds)
            ->orderBy('links.created_at', 'desc')
            ->paginate(20, ['*'], 'linksPage');

        $clicksBase = Click::whereIn('link_id', $linkIds)
            ->where('created_at', '>=', now()->subDays($period)->startOfDay())
            ->where('is_direct_url', false);

        $totalClicks = empty($linkIds) ? 0 : (clone $clicksBase)->count();
        $uniqueVisitors = empty($linkIds) ? 0 : (clone $clicksBase)->distinct('ip_hash')->count('ip_hash');

        $counts = empty($linkIds) ? collect() : (clone $clicksBase)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupByRaw('DATE(created_at)')
            ->pluck('count', 'date')
            ->map(fn ($c) => (int) $c);

        $clicksByDay = collect();
        for ($i = $period - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $clicksByDay->push([
                'date' => $date,
                'label' => now()->subDays($i)->format('M j'),
                'count' => (int) ($counts[$date] ?? 0),
            ]);
        }

        $topLinks = empty($linkIds) ? collect() : $this->groupLinksQuery($user)->with(['domain'])
            ->whereIn('links.id', $linkIds)
            ->orderByDesc('click_count')
            ->limit(10)
            ->get();

        $topCountries = empty($linkIds) ? collect() : (clone $clicksBase)
            ->selectRaw('country_code, COUNT(*) as count')
            ->whereNotNull('country_code')
            ->groupBy('country_code')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $topReferrers = empty($linkIds) ? collect() : (clone $clicksBase)
            ->selectRaw('referrer, COUNT(*) as count')
            ->whereNotNull('referrer')
            ->groupBy('referrer')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $childStats = $group->children()->notRemoved()->get()->map(function (LinkGroup $child) {
            return [
                'group' => $child,
                'links' => count($child->allLinkIds()),
                'clicks' => $child->totalClicks(),
            ];
        });

        $breadcrumb = $group->loadMissing('parent.parent.parent.parent')->breadcrumb();

        return view($this->groupViews().'.show', array_merge(compact(
            'group', 'links', 'period', 'totalClicks', 'uniqueVisitors',
            'clicksByDay', 'topLinks', 'topCountries', 'topReferrers',
            'childStats', 'breadcrumb', 'linkIds'
        ), ['groupScope' => $this->groupScope()]));
    }

    /**
     * Edit group page.
     */
    public function editGroup(string $group)
    {
        $group = auth()->user()->groups()->notRemoved()->with('parent')->findOrFail($group);

        $candidates = auth()->user()->groups()->notRemoved()
            ->where('id', '!=', $group->id)
            ->whereNotIn('id', $group->descendantIds())
            ->orderBy('name')
            ->get();

        return view($this->groupViews().'.edit', [
            'group' => $group,
            'candidates' => $candidates,
            'groupScope' => $this->groupScope(),
        ]);
    }

    /**
     * Update a group. Moving under one of its own descendants is
     * rejected to keep the tree acyclic.
     */
    public function updateGroup(Request $request, string $group)
    {
        $user = auth()->user();
        $group = $user->groups()->notRemoved()->findOrFail($group);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'parent_id' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $parentId = $validated['parent_id'] ?? null;

        if ($parentId === $group->id || in_array($parentId, $group->descendantIds(), true)) {
            return back()->withErrors(['parent_id' => 'A group cannot live inside itself or one of its subgroups.'])->withInput();
        }

        if ($parentId) {
            $user->groups()->notRemoved()->findOrFail($parentId);
        }

        $group->update([
            'name' => trim($validated['name']),
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'] ?? null,
            'parent_id' => $parentId ?: null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        Activity::record('group.updated', $user, $group);

        return redirect()->route($this->groupRoutePrefix().'groups.show', $group->id)
            ->with('success', "Group \"{$group->name}\" updated.");
    }

    /**
     * Soft-delete a group (links stay; subgroups move up one level).
     */
    public function deleteGroup(string $group)
    {
        $user = auth()->user();
        $group = $user->groups()->notRemoved()->findOrFail($group);

        $group->children()->update(['parent_id' => $group->parent_id]);
        $group->update(['is_removed' => true]);

        Activity::record('group.deleted', $user, $group);

        return redirect()->route('dashboard.groups')
            ->with('success', "Group \"{$group->name}\" deleted.");
    }

    /**
     * Attach own links to a group.
     */
    public function addGroupLink(Request $request, string $group)
    {
        $user = auth()->user();
        $group = $user->groups()->notRemoved()->findOrFail($group);

        $validated = $request->validate([
            'link_ids' => ['required', 'array', 'max:50'],
            'link_ids.*' => ['string'],
        ]);

        $owned = $this->groupLinksQuery($user)->whereIn('links.id', $validated['link_ids'])->pluck('links.id')->toArray();
        abort_if(empty($owned), 422, 'No valid links selected.');

        $existing = $group->links()->pluck('links.id')->toArray();
        $next = (int) ($group->items()->max('sort_order') ?? -1) + 1;

        foreach (array_diff($owned, $existing) as $linkId) {
            $group->items()->create(['link_id' => $linkId, 'sort_order' => $next++]);
        }

        Activity::record('group.links_added', $user, $group, ['count' => count($owned)]);

        return back()->with('success', count($owned).' link(s) added to "'.$group->name.'".');
    }

    /**
     * Detach a link from a group (the link itself is untouched).
     */
    public function removeGroupLink(string $group, string $link)
    {
        $user = auth()->user();
        $group = $user->groups()->notRemoved()->findOrFail($group);

        $group->items()->where('link_id', $link)->delete();

        Activity::record('group.link_removed', $user, $group, ['link_id' => $link]);

        return back()->with('success', 'Link removed from group.');
    }
}
