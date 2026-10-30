<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->boolean('health_check_enabled')->default(false)->index();
            $table->timestamp('last_health_check_at')->nullable()->index();
            $table->string('health_status', 32)->nullable()->index(); // healthy, degraded, unreachable
            $table->unsignedSmallInteger('health_status_code')->nullable();
            $table->unsignedInteger('health_response_time_ms')->nullable();
            $table->string('health_error', 500)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->dropIndex(['health_check_enabled']);
            $table->dropIndex(['last_health_check_at']);
            $table->dropIndex(['health_status']);
            $table->dropColumn([
                'health_check_enabled',
                'last_health_check_at',
                'health_status',
                'health_status_code',
                'health_response_time_ms',
                'health_error',
            ]);
        });
    }
};
