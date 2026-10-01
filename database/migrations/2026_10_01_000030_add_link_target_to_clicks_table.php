<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clicks', function (Blueprint $table) {
            $table->foreignUlid('link_target_id')->nullable()->after('link_id')->constrained('link_targets')->nullOnDelete();
            $table->index(['link_id', 'link_target_id']);
        });
    }

    public function down(): void
    {
        Schema::table('clicks', function (Blueprint $table) {
            $table->dropForeign(['link_target_id']);
            $table->dropIndex(['link_id', 'link_target_id']);
            $table->dropColumn('link_target_id');
        });
    }
};
