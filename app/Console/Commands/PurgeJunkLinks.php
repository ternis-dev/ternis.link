<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Link;
use App\Services\JunkUrlDetector;
use App\Services\LinkService;
use App\Support\Activity;
use Illuminate\Console\Command;

/**
 * Find links whose destination URL matches scanner-junk heuristics
 * (phpinfo probes, .env backups, server-status payloads, …).
 *
 * Dry-run by default — prints the candidates. Pass --apply to
 * soft-deactivate them (analytics are preserved, same as the
 * admin Deactivate action). Already-inactive links are skipped.
 */
class PurgeJunkLinks extends Command
{
    protected $signature = 'links:purge-junk
        {--apply : Deactivate matching links (default is a dry-run listing)}
        {--limit=1000 : Maximum number of links to scan, newest first}';

    protected $description = 'List (or deactivate with --apply) links whose destination URL looks like scanner junk.';

    public function handle(JunkUrlDetector $detector, LinkService $links): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $apply = (bool) $this->option('apply');

        $candidates = collect();
        $scanned = 0;

        Link::orderByDesc('id')
            ->chunk(500, function ($chunk) use ($detector, $candidates, &$scanned, $limit) {
                foreach ($chunk as $link) {
                    if ($scanned >= $limit) {
                        return false;
                    }
                    $scanned++;

                    if (! $link->is_active) {
                        continue;
                    }

                    $reasons = $detector->reasons((string) $link->destination_url);

                    if ($reasons !== []) {
                        $candidates->push([$link, $reasons]);
                    }
                }

                return true;
            });

        if ($candidates->isEmpty()) {
            $this->info("Scanned {$scanned} link(s) — no junk found.");

            return self::SUCCESS;
        }

        $rows = $candidates->map(fn (array $item) => [
            $item[0]->slug,
            mb_strimwidth((string) $item[0]->destination_url, 0, 60, '…'),
            $item[0]->user_id === null ? 'Guest' : "#{$item[0]->user_id}",
            $item[0]->created_at?->format('Y-m-d') ?? '—',
            implode('; ', $item[1]),
        ]);

        $this->table(['Slug', 'Destination', 'Owner', 'Created', 'Why junk'], $rows);

        if (! $apply) {
            $this->comment("Dry-run: {$candidates->count()} junk link(s) found, nothing changed. Re-run with --apply to deactivate.");

            return self::SUCCESS;
        }

        foreach ($candidates as [$link]) {
            $links->deactivate($link);
        }

        $this->info("Deactivated {$candidates->count()} junk link(s). Analytics preserved.");

        Activity::record(ActivityLog::SYSTEM_JUNK_LINKS_PURGED, null, null, [
            'count' => $candidates->count(),
        ]);

        return self::SUCCESS;
    }
}
