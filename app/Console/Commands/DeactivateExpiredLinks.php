<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Link;
use App\Support\Activity;
use Illuminate\Console\Command;

class DeactivateExpiredLinks extends Command
{
    protected $signature = 'links:deactivate-expired';

    protected $description = 'Deactivate links past their expiration date (clicks and analytics are preserved).';

    public function handle(): int
    {
        // Bulk query-builder updates skip model events, so collect the
        // affected slug keys first and forget them after deactivation.
        // Otherwise the redirect cache could serve expired links until TTL.
        $affected = Link::where('is_active', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get(['domain_id', 'slug']);

        $count = Link::where('is_active', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['is_active' => false]);

        foreach ($affected as $link) {
            Link::forgetCachedSlug($link->domain_id, $link->slug);
        }

        $this->info("Deactivated {$count} expired link(s).");

        if ($count > 0) {
            Activity::record(ActivityLog::SYSTEM_EXPIRED_LINKS_DEACTIVATED, null, null, [
                'count' => $count,
            ]);
        }

        return self::SUCCESS;
    }
}
