<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class EnvBackupTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir().'/env-backup-test-'.uniqid();
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    public function test_backup_writes_prunes_and_skips_unchanged(): void
    {
        file_put_contents($this->dir.'/.env', "APP_KEY=base64:testkey123\nAPP_ENV=production\n");
        touch($this->dir.'/.env.backup-20200101', time() - 40 * 86400);
        touch($this->dir.'/.env.backup-20200102', time() - 39 * 86400);

        $this->artisan('env:backup', ['--dir' => $this->dir, '--keep' => 2])
            ->assertSuccessful();

        $today = now()->format('Ymd');
        $this->assertFileExists($this->dir."/.env.backup-{$today}");
        $this->assertSame("APP_KEY=base64:testkey123\nAPP_ENV=production\n",
            file_get_contents($this->dir."/.env.backup-{$today}"));
        // 3 backups, keep=2 → oldest pruned, middle kept.
        $this->assertFileDoesNotExist($this->dir.'/.env.backup-20200101');
        $this->assertFileExists($this->dir.'/.env.backup-20200102');

        // Nothing changed → no rewrite, still successful.
        $this->artisan('env:backup', ['--dir' => $this->dir, '--keep' => 2])
            ->assertSuccessful()
            ->expectsOutputToContain('No changes');
    }

    public function test_backup_fails_without_env_file(): void
    {
        $this->artisan('env:backup', ['--dir' => $this->dir])
            ->assertFailed();
    }
}
