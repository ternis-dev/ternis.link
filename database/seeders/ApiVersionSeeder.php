<?php

namespace Database\Seeders;

use App\Enums\ApiVersionStatus;
use App\Models\ApiVersion;
use Illuminate\Database\Seeder;

class ApiVersionSeeder extends Seeder
{
    public function run(): void
    {
        ApiVersion::updateOrCreate(
            ['version' => 1],
            ['status' => ApiVersionStatus::Active],
        );
    }
}
