<?php

namespace App\Services;

use App\Models\Link;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LinkDeactivationUndo
{
    public function remember(Link $link, User $actor): string
    {
        $token = (string) Str::uuid();
        Cache::put('undo-link:'.$actor->id.':'.$token, [
            'id' => $link->id,
            'attributes' => $link->fresh()->getRawOriginal(),
        ], now()->addMinutes(2));

        return $token;
    }

    public function restore(string $token, User $actor): bool
    {
        $snapshot = Cache::pull('undo-link:'.$actor->id.':'.$token);
        if (! is_array($snapshot)) {
            return false;
        }

        return DB::transaction(function () use ($snapshot, $actor) {
            $link = Link::query()->lockForUpdate()->find($snapshot['id']);
            // Refuse stale restores, removed/quarantined links, and changed ownership.
            if (! $link || ($link->user_id !== $actor->id && ! $actor->isAdmin())
                || $link->is_active || $link->is_removed || in_array('quarantine', $link->tags ?? [], true)
                || $link->getRawOriginal() !== $snapshot['attributes']) {
                return false;
            }
            $link->update(['is_active' => true]);
            Link::forgetCachedSlug($link->domain_id, $link->slug);
            Activity::record('link.reactivated', $actor, $link, ['via' => 'undo']);

            return true;
        });
    }
}
