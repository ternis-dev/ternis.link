<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('api_versions')->insertOrIgnore([
            'version' => 2,
            'status' => 'active',
            'created_at' => now(),
        ]);
        Cache::forget('api_version:latest');
        Cache::forget('api_version:2:meta');
    }

    public function down(): void
    {
        // Keep the version record: existing API keys may reference it.
        DB::table('api_versions')->where('version', 2)->update(['status' => 'retired']);
        Cache::forget('api_version:latest');
        Cache::forget('api_version:2:meta');
    }
};
