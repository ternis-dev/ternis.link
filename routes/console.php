<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('links:deactivate-expired')->daily();
Schedule::command('bio:deactivate-expired')->daily();
Schedule::command('privacy:prune-ips')->daily();
Schedule::command('privacy:prune-session-tokens')->daily();
Schedule::command('env:backup')->dailyAt('03:00');

// Spatie backups
Schedule::command('backup:clean')->dailyAt('01:00');
Schedule::command('backup:run')->dailyAt('01:30');
Schedule::command('backup:monitor')->dailyAt('03:30');
