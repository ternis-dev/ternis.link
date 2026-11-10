<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->integer('rate_limit_per_minute')->nullable()->default(10)->change();
        });
        DB::table('plans')->insertOrIgnore([
            'id' => (string) Str::ulid(), 'name' => 'unlimited',
            'min_slug_length' => 1, 'custom_subdomain' => true, 'slug_length_choice' => true,
            'rate_limit_per_minute' => null, 'max_links_per_day' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Preserve assigned plans on rollback. Convert unlimited creation rates
        // to a finite fallback before restoring the non-null column.
        DB::table('plans')->whereNull('rate_limit_per_minute')->update(['rate_limit_per_minute' => 300]);
        Schema::table('plans', function (Blueprint $table) {
            $table->integer('rate_limit_per_minute')->nullable(false)->default(10)->change();
        });
    }
};
