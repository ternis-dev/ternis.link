<?php

namespace App\Console\Commands;

use App\Models\Link;
use Illuminate\Console\Command;

class DeactivateExpiredLinks extends Command
{
    protected $signature = 'links:deactivate-expired';

    protected $description = 'Deactivate links past their expiration date (clicks and analytics are preserved).';

    public function handle(): int
    {
        $count = Link::where('is_active', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['is_active' => false]);

        $this->info("Deactivated {$count} expired link(s).");

        return self::SUCCESS;
    }
}
