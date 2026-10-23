<?php

namespace App\Support;

use Illuminate\Database\Migrations\Migrator;

class MigrationReadiness
{
    public function __construct(private Migrator $migrator) {}

    public function ready(): bool
    {
        if (! $this->migrator->repositoryExists()) {
            return false;
        }

        $files = $this->migrator->getMigrationFiles(array_merge([database_path('migrations')], $this->migrator->paths()));

        return array_diff(array_keys($files), $this->migrator->getRepository()->getRan()) === [];
    }
}
