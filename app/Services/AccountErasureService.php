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

            // Activity rows keep aggregates without user join.
            ActivityLog::where('user_id', $user->id)->delete();

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
