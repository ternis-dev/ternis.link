<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Opt-in to the legacy my.ternis.link dashboard. The new public
     * dashboard is default; legacy lives under /_legacy/.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('public_dashboard_legacy')->default(false)->after('theme');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('public_dashboard_legacy');
        });
    }
};
