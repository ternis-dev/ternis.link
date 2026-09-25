<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\ApiKey;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Single entry point for the user-action audit trail. Never throws —
 * auditing must not break the action being audited (e.g. when the
 * database is down or the call happens on a console without HTTP).
 *
 * Actor defaults to the authenticated user; pass null explicitly for
 * guest/system actions. The subject owner is derived from the
 * subject's user_id when available so admin-on-user actions stay
 * visible in the affected user's own history.
 */
final class Activity
{
    public static function record(
        string $action,
        ?User $actor = null,
        ?Model $subject = null,
        array $metadata = [],
        ?User $owner = null,
    ): void {
        try {
            $actor ??= auth()->user();

            if ($owner === null && $subject !== null && isset($subject->getAttributes()['user_id'])) {
                $ownerId = $subject->getAttributes()['user_id'];
                $owner = $ownerId ? User::find($ownerId) : null;
            }

            $request = app()->runningInConsole() ? null : request();

            ActivityLog::create([
                'actor_id' => $actor?->id,
                'action' => mb_substr($action, 0, 64),
                'subject_type' => $subject ? $subject->getMorphClass() : null,
                'subject_id' => $subject?->getKey(),
                'subject_owner_id' => $owner?->id,
                'subject_label' => $subject ? self::labelFor($subject) : null,
                'metadata' => $metadata !== [] ? $metadata : null,
                'ip_hash' => $request ? IpHash::make($request->ip()) : null,
                'user_agent' => ($ua = $request?->userAgent()) ? mb_substr($ua, 0, 512) : null,
            ]);
        } catch (Throwable) {
            // Auditing an action must never break the action itself.
        }
    }

    /**
     * Short human-readable label so a row stays useful after the
     * subject is deactivated (or hard-deleted later).
     */
    private static function labelFor(Model $subject): ?string
    {
        return match (true) {
            $subject instanceof Link => mb_substr($subject->slug ?? $subject->id, 0, 255),
            $subject instanceof Domain => mb_substr($subject->hostname ?? $subject->id, 0, 255),
            $subject instanceof User => mb_substr($subject->email ?? $subject->id, 0, 255),
            $subject instanceof ApiKey => mb_substr(
                trim(($subject->name ?? '').' ('.($subject->key_prefix ?? '?').'…)'), 0, 255
            ),
            default => mb_substr((string) $subject->getKey(), 0, 255),
        };
    }
}
