<?php

namespace App\Console\Commands;

use App\Models\Link;
use App\Services\LinkHealthService;
use Illuminate\Console\Command;

class CheckLinkHealthCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'links:check-health
        {--limit=50 : Maximum number of links to check in this run}
        {--chunk=25 : Database batch query chunk size}
        {--delay-ms=100 : Milliseconds to wait between external HTTP requests}
        {--failed-only : Only check links that are currently degraded or unreachable}
        {--untested-only : Only check links that have never had a health check}
        {--all : Check all links with health monitoring enabled, including inactive ones}
        {--dry-run : Probe destinations and display results without writing to the database}
        {--no-notify : Suppress email/security notifications for unreachable destinations}';

    /**
     * The console command description.
     */
    protected $description = 'Perform automated health monitoring checks on opted-in short link destination URLs.';

    public function handle(LinkHealthService $healthService): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $chunkSize = max(1, (int) $this->option('chunk'));
        $delayMs = max(0, (int) $this->option('delay-ms'));
        $dryRun = (bool) $this->option('dry-run');
        $notify = ! $this->option('no-notify');

        $query = Link::query()
            ->with(['domain', 'user'])
            ->where('health_check_enabled', true)
            ->where('is_removed', false);

        if (! $this->option('all')) {
            $query->where('is_active', true);
        }

        if ($this->option('failed-only')) {
            $query->whereIn('health_status', ['degraded', 'unreachable']);
        } elseif ($this->option('untested-only')) {
            $query->whereNull('last_health_check_at');
        }

        // Check links that haven't been checked recently first
        $query->orderByRaw('last_health_check_at IS NOT NULL, last_health_check_at ASC');

        $totalFound = (clone $query)->count();

        if ($totalFound === 0) {
            $this->info('No opted-in links require health checks matching the given filters.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Starting health check run (limit: %d, eligible: %d, dry-run: %s, delay: %dms)...',
            $limit,
            $totalFound,
            $dryRun ? 'yes' : 'no',
            $delayMs
        ));

        $processed = 0;
        $healthyCount = 0;
        $degradedCount = 0;
        $unreachableCount = 0;
        $tableRows = [];

        $query->chunkById($chunkSize, function ($links) use (
            $healthService,
            $limit,
            $dryRun,
            $notify,
            $delayMs,
            &$processed,
            &$healthyCount,
            &$degradedCount,
            &$unreachableCount,
            &$tableRows
        ) {
            foreach ($links as $link) {
                if ($processed >= $limit) {
                    return false;
                }

                if ($dryRun) {
                    $result = $healthService->probe($link->destination_url);
                } else {
                    $result = $healthService->check($link, notify: $notify);
                }

                $status = $result['status'];
                match ($status) {
                    'healthy' => $healthyCount++,
                    'degraded' => $degradedCount++,
                    'unreachable' => $unreachableCount++,
                    default => null,
                };

                $tableRows[] = [
                    'slug' => $link->slug,
                    'destination' => mb_strimwidth($link->destination_url, 0, 40, '…'),
                    'status' => strtoupper($status),
                    'http_code' => $result['status_code'] ?? 'ERR',
                    'latency' => "{$result['response_time_ms']}ms",
                    'error' => $result['error'] ?? 'None',
                ];

                $processed++;

                if ($delayMs > 0 && $processed < $limit) {
                    usleep($delayMs * 1000);
                }
            }

            return $processed < $limit;
        });

        $this->table(['Slug', 'Destination', 'Status', 'HTTP Code', 'Latency', 'Error Details'], $tableRows);

        $this->newLine();
        $this->info(sprintf(
            'Health check run finished: %d processed | Healthy: %d | Degraded: %d | Unreachable: %d',
            $processed,
            $healthyCount,
            $degradedCount,
            $unreachableCount
        ));

        return self::SUCCESS;
    }
}
