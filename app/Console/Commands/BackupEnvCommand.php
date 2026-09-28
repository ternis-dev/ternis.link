<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Daily .env backup (runs via the scheduler: `env:backup --keep=30`).
 *
 * Copies the environment file to a timestamped sibling
 * (`.env.backup-YYYYMMDD`), chmod 600, and prunes backups older than
 * the retention window. Skips writing when nothing changed since the
 * newest backup. The timestamped names are gitignored (`.env.backup-*`)
 * so secrets never enter the repo.
 *
 * Why this exists: a lost or rotated APP_KEY orphans every encrypted
 * value (SSO tokens, stored IPs). The backup is the zero-loss restore
 * path — copy the newest file back over `.env` and reload config.
 */
class BackupEnvCommand extends Command
{
    protected $signature = 'env:backup
        {--keep=30 : How many daily backups to retain}
        {--dir= : Directory holding .env (defaults to the app root; mainly for tests)}';

    protected $description = 'Back up .env to a timestamped, gitignored copy and prune old ones.';

    public function handle(): int
    {
        $dir = $this->option('dir') ?: base_path();
        $source = rtrim($dir, '/').'/.env';

        if (! is_file($source)) {
            $this->error("No .env found at {$source}.");

            return self::FAILURE;
        }

        $keep = max(1, (int) $this->option('keep'));
        $today = Carbon::now()->format('Ymd');
        $target = rtrim($dir, '/')."/.env.backup-{$today}";

        $backups = $this->backups($dir);

        if ($backups !== []) {
            $newest = $this->newest($backups);

            if (hash_file('sha256', $source) === hash_file('sha256', $newest)) {
                $this->line('No changes since '.$this->basename($newest).' — skipping write.');
                $this->prune($backups, $keep);

                return self::SUCCESS;
            }
        }

        if (@copy($source, $target) !== true) {
            $this->error("Could not write {$target}.");

            return self::FAILURE;
        }

        @chmod($target, 0600);

        if (strpos((string) file_get_contents($target), 'APP_KEY=') === false) {
            @unlink($target);
            $this->error('Backup looks wrong (no APP_KEY line) — removed it.');

            return self::FAILURE;
        }

        $this->info('Backed up to '.$this->basename($target).'.');
        $this->prune($this->backups($dir), $keep);

        return self::SUCCESS;
    }

    /** @return list<string> oldest-first backup paths */
    private function backups(string $dir): array
    {
        $paths = glob(rtrim($dir, '/').'/.env.backup-*') ?: [];
        sort($paths);

        return array_values(array_filter($paths, 'is_file'));
    }

    private function newest(array $backups): string
    {
        return end($backups);
    }

    private function basename(string $path): string
    {
        return basename($path);
    }

    /** @param list<string> $backups */
    private function prune(array $backups, int $keep): void
    {
        foreach (array_slice($backups, 0, max(0, count($backups) - $keep)) as $stale) {
            if (@unlink($stale)) {
                $this->line('Pruned '.$this->basename($stale).'.');
            }
        }
    }
}
