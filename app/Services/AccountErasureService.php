<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AccountErasureService
{
    public function erase(User $user): void
    {
        DB::transaction(function () use ($user) {
            // Anonymize owned links (keep aggregates, drop ownership + IPs).
            $user->links()->update([
                'user_id' => null,
                'creator_ip_hash' => null,
                'creator_ip_encrypted' => null,
            ]);

            $user->apiKeys()->delete();
            $user->oauthIdentity()->delete();
            $user->notifications()->delete();
            $user->bulkOperations()->delete();
            $user->privacyExports()->delete();
            $user->apiSessionTokens()->delete();

            /*
             * Activity rows keep aggregates without a user join — but
             * the user's own rows are theirs to erase (Art. 17), so they
             * go with the account. The FKs on actor_id/subject_owner_id
             * are nullOnDelete and would only anonymize the rows.
             *
             * NOTE: activity_logs has no `user_id` column — querying it
             * deletes nothing on SQLite (a double-quoted string is a
             * literal there) and is a hard SQL error on MySQL/PostgreSQL,
             * which aborted the whole erasure transaction.
             */
            ActivityLog::where('actor_id', $user->id)
                ->orWhere('subject_owner_id', $user->id)
                ->delete();

            $user->delete();
        });
    }

    public function schedule(User $user): \DateTimeImmutable
    {
        $user->update(['deletion_requested_at' => now()]);
        $days = (int) config('privacy.deletion_grace_days', 14);

        return new \DateTimeImmutable("+{$days} days");
    }

    public function cancel(User $user): void
    {
        $user->update(['deletion_requested_at' => null]);
    }
}
