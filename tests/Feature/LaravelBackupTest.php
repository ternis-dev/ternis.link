<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class LaravelBackupTest extends TestCase
{
    public function test_r2_filesystem_disk_is_configured(): void
    {
        $diskConfig = config('filesystems.disks.r2');

        $this->assertIsArray($diskConfig);
        $this->assertSame('s3', $diskConfig['driver']);
        $this->assertFalse($diskConfig['use_path_style_endpoint']);
        $this->assertTrue($diskConfig['throw']);
    }

    public function test_backup_configuration_has_expected_defaults(): void
    {
        $backupConfig = config('backup');

        $this->assertIsArray($backupConfig);
        $this->assertIsArray(config('backup.backup.destination.disks'));
        $this->assertContains(base_path('.git'), config('backup.backup.source.files.exclude'));
        $this->assertContains(base_path('vendor'), config('backup.backup.source.files.exclude'));
        $this->assertContains(base_path('node_modules'), config('backup.backup.source.files.exclude'));
        $this->assertContains(storage_path('framework'), config('backup.backup.source.files.exclude'));
    }

    public function test_backup_commands_are_scheduled(): void
    {
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());

        $commands = $events->map(fn ($event) => $event->command)->filter()->values();

        $this->assertTrue($commands->contains(fn ($cmd) => str_contains((string) $cmd, 'backup:clean')));
        $this->assertTrue($commands->contains(fn ($cmd) => str_contains((string) $cmd, 'backup:run')));
        $this->assertTrue($commands->contains(fn ($cmd) => str_contains((string) $cmd, 'backup:monitor')));
    }

    public function test_artisan_backup_list_runs_successfully(): void
    {
        $this->artisan('backup:list')
            ->assertSuccessful();
    }
}
