<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\BioPage;
use App\Support\Activity;
use Illuminate\Console\Command;

class DeactivateExpiredBioPages extends Command
{
    protected $signature = 'bio:deactivate-expired';

    protected $description = 'Deactivate bio pages past their expiration date (buttons and analytics are preserved).';

    public function handle(): int
    {
        // Model events flush the public render cache per row; bulk
        // updates would leave stale pages cached until TTL.
        $pages = BioPage::where('is_active', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get(['id']);

        $count = 0;
        foreach ($pages as $page) {
            $page->update(['is_active' => false]);
            $count++;
        }

        $this->info("Deactivated {$count} expired bio page(s).");

        if ($count > 0) {
            Activity::record(ActivityLog::SYSTEM_EXPIRED_LINKS_DEACTIVATED, null, null, [
                'count' => $count,
                'scope' => 'bio',
            ]);
        }

        return self::SUCCESS;
    }
}
