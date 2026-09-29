<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;

/**
 * Delete activity/audit rows past the retention window (3 years).
 *
 * The trail is append-only and user-visible (dashboard + admin
 * console), but GDPR storage limitation (Art. 5(1)(e)) forbids
 * keeping IP hashes, user agents and labels "forever" without a
 * legal obligation. Three years covers civil limitation periods
 * plus a security margin; per-link attribution survives on the
 * link rows themselves (api_key_id), aggregates never needed
 * deletion because they were never stored here.
 */
class PruneActivityLogs extends Command
{
    protected $signature = 'privacy:prune-activity-logs';

    protected $description = 'Delete activity log rows older than the retention window (3 years).';

    public function handle(): int
    {
        $cutoff = now()->subDays(ActivityLog::RETENTION_DAYS);

        $deleted = ActivityLog::where('created_at', '<', $cutoff)->delete();

        $this->info("Pruned activity logs: {$deleted} row(s) older than {$cutoff->format('Y-m-d')}.");

        return self::SUCCESS;
    }
}
