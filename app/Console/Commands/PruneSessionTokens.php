<?php

namespace App\Console\Commands;

use App\Models\ApiSessionToken;
use Illuminate\Console\Command;

/**
 * Delete dead session tokens of the public dashboard's auth API.
 *
 * A session token is a credential: once it expired or was revoked it
 * can never authenticate anything again, so keeping the row only adds
 * noise (and a temptation to treat stale rows as live ones). Rows are
 * kept for `privacy.session_token_retention_days` after they died, so
 * "when did this token expire?" stays answerable for a while.
 */
class PruneSessionTokens extends Command
{
    protected $signature = 'privacy:prune-session-tokens';

    protected $description = 'Delete expired or revoked session tokens of the auth API.';

    public function handle(): int
    {
        $cutoff = now()->subDays((int) config('privacy.session_token_retention_days', 30));

        $deleted = ApiSessionToken::where(function ($query) use ($cutoff) {
            $query->where('expires_at', '<', $cutoff)
                ->orWhere(function ($revoked) use ($cutoff) {
                    $revoked->whereNotNull('revoked_at')
                        ->where('revoked_at', '<', $cutoff);
                });
        })->delete();

        $this->info("Pruned {$deleted} session token(s) dead before {$cutoff->format('Y-m-d H:i')}.");

        return self::SUCCESS;
    }
}
