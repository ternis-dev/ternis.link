<?php

namespace Database\Seeders;

use App\Enums\ApiVersionStatus;
use App\Models\ApiVersion;
use Illuminate\Database\Seeder;

class ApiVersionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([1, 2] as $version) {
            ApiVersion::updateOrCreate(
                ['version' => $version],
                ['status' => ApiVersionStatus::Active],
            );
        }
    }
}
