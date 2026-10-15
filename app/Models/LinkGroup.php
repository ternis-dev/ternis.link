<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LinkGroup extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'user_id',
        'parent_id',
        'name',
        'description',
        'color',
        'sort_order',
        'is_active',
        'is_removed',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'is_removed' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(LinkGroup::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(LinkGroup::class, 'parent_id')->orderBy('sort_order');
    }

    public function links(): BelongsToMany
    {
        return $this->belongsToMany(Link::class, 'link_group_items')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('link_group_items.sort_order');
    }

    public function items(): HasMany
    {
        return $this->hasMany(LinkGroupItem::class)->orderBy('sort_order');
    }

    /**
     * Scope: exclude removed groups.
     */
    public function scopeNotRemoved(Builder $query): Builder
    {
        return $query->where('is_removed', false);
    }

    /**
     * Scope: only root-level groups (no parent).
     */
    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope: only child groups of a given parent.
     */
    public function scopeChildOf(Builder $query, string $parentId): Builder
    {
        return $query->where('parent_id', $parentId);
    }

    /**
     * Get all descendant group IDs (recursive).
     *
     * @return array<string>
     */
    public function descendantIds(): array
    {
        $ids = [];
        $queue = [$this->id];

        while (! empty($queue)) {
            $current = array_shift($queue);
            $children = static::where('parent_id', $current)
                ->where('is_removed', false)
                ->pluck('id')
                ->toArray();

            foreach ($children as $childId) {
                $ids[] = $childId;
                $queue[] = $childId;
            }
        }

        return $ids;
    }

    /**
     * Get all link IDs in this group and all descendant groups.
     *
     * @return array<string>
     */
    public function allLinkIds(): array
    {
        $groupIds = array_merge([$this->id], $this->descendantIds());

        return DB::table('link_group_items')
            ->whereIn('link_group_id', $groupIds)
            ->pluck('link_id')
            ->toArray();
    }

    /**
     * Get combined click count for this group and all descendants.
     */
    public function totalClicks(): int
    {
        $linkIds = $this->allLinkIds();

        if (empty($linkIds)) {
            return 0;
        }

        return (int) DB::table('links')
            ->whereIn('id', $linkIds)
            ->where('is_removed', false)
            ->sum('click_count');
    }

    /**
     * Get the full breadcrumb path from root to this group.
     *
     * @return Collection<int, LinkGroup>
     */
    public function breadcrumb()
    {
        $path = collect([$this]);
        $current = $this;

        while ($current->parent) {
            $current = $current->parent;
            $path->prepend($current);
        }

        return $path;
    }
}
