<?php

namespace App\Console\Commands;

use App\Models\ApiRequestLog;
use Illuminate\Console\Command;

/**
 * Delete API request logs past the retention window (90 days).
 * No aggregates are kept — the table is a rolling operational
 * window for abuse triage and capacity planning, not history.
 */
class PruneApiRequestLogs extends Command
{
    protected $signature = 'privacy:prune-api-logs';

    protected $description = 'Delete API request logs older than the retention window (90 days).';

    public function handle(): int
    {
        $cutoff = now()->subDays(ApiRequestLog::RETENTION_DAYS);

        $deleted = ApiRequestLog::where('created_at', '<', $cutoff)->delete();

        $this->info("Pruned API request logs: {$deleted} row(s) older than {$cutoff->format('Y-m-d')}.");

        return self::SUCCESS;
    }
}
