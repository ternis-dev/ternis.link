<?php

namespace App\Console\Commands;

use App\Support\MigrationReadiness;
use Illuminate\Console\Command;

class CheckDeployment extends Command
{
    protected $signature = 'deploy:check';

    protected $description = 'Fail deployment when database migrations are missing';

    public function handle(MigrationReadiness $migrations): int
    {
        if (! $migrations->ready()) {
            $this->error('Unapplied database migrations. Run php artisan migrate --force before serving this release.');

            return self::FAILURE;
        }

        $this->info('All database migrations have run.');

        return self::SUCCESS;
    }
}
