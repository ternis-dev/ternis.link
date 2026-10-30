<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Link;
use App\Services\AiThreatDetectionService;
use App\Support\Activity;
use Illuminate\Console\Command;

class ScanExistingLinksCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'security:scan-existing
        {--limit=50 : Maximum number of links to process in this run}
        {--chunk=25 : Database batch query chunk size}
        {--delay-ms=250 : Milliseconds to wait between external AI evaluations to avoid rate limits}
        {--dry-run : Simulate verdicts and print actions without updating links or persisting threat records}
        {--unscanned-only : Only evaluate links that have no existing threat evaluations}
        {--guests-only : Only evaluate anonymous links created by guests (user_id IS NULL)}
        {--suspicious-only : Only evaluate links whose destination URL triggers heuristic suspicion}
        {--all : Scan all links regardless of is_active status (default: active links only)}
        {--force : Run scan even if AI security is disabled in configuration}';

    /**
     * The console command description.
     */
    protected $description = 'Audit and scan existing links with TypeSafe AI threat detection in rate-limited batches.';

    public function handle(AiThreatDetectionService $threatService): int
    {
        if (! $threatService->isEnabled() && ! $this->option('force')) {
            $this->error('AI Threat Detection is disabled in configuration (ai_security.enabled). Use --force to proceed anyway.');

            return self::FAILURE;
        }

        $limit = max(1, (int) $this->option('limit'));
        $chunkSize = max(1, (int) $this->option('chunk'));
        $delayMs = max(0, (int) $this->option('delay-ms'));
        $dryRun = (bool) $this->option('dry-run');
        $suspiciousOnly = (bool) $this->option('suspicious-only');

        $query = Link::query()
            ->with(['domain', 'user', 'apiKey.user', 'threatEvaluations'])
            ->orderByDesc('id');

        if (! $this->option('all')) {
            $query->where('is_active', true);
        }

        if ($this->option('guests-only')) {
            $query->whereNull('user_id');
        }

        if ($this->option('unscanned-only')) {
            $query->whereDoesntHave('threatEvaluations');
        }

        $processed = 0;
        $quarantined = 0;
        $botProtected = 0;
        $cleared = 0;
        $bypassed = 0;
        $errors = 0;
        $skipped = 0;

        $tableRows = [];

        $query->chunk($chunkSize, function ($links) use (
            $threatService,
            $limit,
            $delayMs,
            $dryRun,
            $suspiciousOnly,
            &$processed,
            &$quarantined,
            &$botProtected,
            &$cleared,
            &$bypassed,
            &$errors,
            &$skipped,
            &$tableRows
        ) {
            foreach ($links as $link) {
                if ($processed >= $limit) {
                    return false;
                }

                if ($suspiciousOnly && ! $threatService->isSuspiciousUrl((string) $link->destination_url)) {
                    $skipped++;
                    continue;
                }

                // Check for admin API key bypass
                if ($link->apiKey?->shouldBypassAi()) {
                    $bypassed++;
                    $processed++;
                    $tableRows[] = [
                        $link->slug,
                        mb_strimwidth((string) $link->destination_url, 0, 45, '…'),
                        $link->user_id ? "#{$link->user_id}" : 'Guest',
                        'bypassed',
                        '0/100',
                        'bypassed (admin key)',
                    ];
                    continue;
                }

                $processed++;

                $evaluation = $threatService->evaluateLink($link, [
                    'dry_run' => $dryRun,
                    'skip_burst_check' => true,
                    'evaluation_type' => 'batch_scan',
                ]);

                if ($evaluation === null) {
                    $errors++;
                    $tableRows[] = [
                        $link->slug,
                        mb_strimwidth((string) $link->destination_url, 0, 45, '…'),
                        $link->user_id ? "#{$link->user_id}" : 'Guest',
                        'unverified',
                        '—',
                        'skipped / error',
                    ];
                } else {
                    if ($evaluation->action_taken === 'quarantined') {
                        $quarantined++;
                    } elseif ($evaluation->action_taken === 'bot_protected') {
                        $botProtected++;
                    } else {
                        $cleared++;
                    }

                    $actionDisplay = $dryRun ? "dry-run ({$evaluation->action_taken})" : $evaluation->action_taken;

                    $tableRows[] = [
                        $link->slug,
                        mb_strimwidth((string) $link->destination_url, 0, 45, '…'),
                        $link->user_id ? "#{$link->user_id}" : 'Guest',
                        $evaluation->verdict,
                        "{$evaluation->threat_score}/100",
                        $actionDisplay,
                    ];
                }

                if ($delayMs > 0 && $processed < $limit) {
                    usleep($delayMs * 1000);
                }
            }

            return true;
        });

        if ($processed === 0) {
            $msg = 'No matching links found to scan.';
            if ($skipped > 0) {
                $msg .= " ({$skipped} link(s) skipped due to --suspicious-only filter).";
            }
            $this->info($msg);

            return self::SUCCESS;
        }

        $this->table(['Slug', 'Destination URL', 'Owner', 'Verdict', 'Score', 'Action'], $tableRows);

        if ($dryRun) {
            $this->comment(sprintf(
                'Dry run complete: %d link(s) inspected (%d would quarantine, %d would bot-protect, %d cleared, %d bypassed). No database modifications made.',
                $processed,
                $quarantined,
                $botProtected,
                $cleared,
                $bypassed
            ));
        } else {
            Activity::record(ActivityLog::SYSTEM_THREAT_SCAN_COMPLETED, null, null, [
                'processed' => $processed,
                'quarantined' => $quarantined,
                'bot_protected' => $botProtected,
                'cleared' => $cleared,
                'bypassed' => $bypassed,
                'errors' => $errors,
            ]);

            $this->info(sprintf(
                'Scan complete: %d link(s) evaluated (%d quarantined, %d bot-protected, %d cleared, %d bypassed).',
                $processed,
                $quarantined,
                $botProtected,
                $cleared,
                $bypassed
            ));
        }

        return self::SUCCESS;
    }
}
