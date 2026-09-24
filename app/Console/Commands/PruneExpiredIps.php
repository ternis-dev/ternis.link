<?php

namespace App\Console\Commands;

use App\Models\Click;
use App\Models\Link;
use App\Support\IpCapture;
use Illuminate\Console\Command;

/**
 * Irreversibly delete expired encrypted IPs (abuse-forensics window).
 * Nullifies clicks.ip_encrypted and links.creator_ip_encrypted older
 * than the retention window; hashes, analytics and links are untouched.
 */
class PruneExpiredIps extends Command
{
    protected $signature = 'privacy:prune-ips';

    protected $description = 'Delete encrypted IPs past the retention window (hashes and analytics are kept).';

    public function handle(): int
    {
        $cutoff = now()->subDays(IpCapture::retentionDays());

        $clicks = Click::whereNotNull('ip_encrypted')
            ->where('created_at', '<', $cutoff)
            ->update(['ip_encrypted' => null]);

        $links = Link::whereNotNull('creator_ip_encrypted')
            ->where('created_at', '<', $cutoff)
            ->update(['creator_ip_encrypted' => null]);

        $this->info("Pruned encrypted IPs: {$clicks} click(s), {$links} link(s) older than {$cutoff->format('Y-m-d')}.");

        return self::SUCCESS;
    }
}
