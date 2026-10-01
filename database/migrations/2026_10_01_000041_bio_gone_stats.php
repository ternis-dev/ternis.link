<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bio_pages', function (Blueprint $table) {
            $table->string('gone_url', 2048)->nullable()->after('expires_at');
            $table->boolean('show_stats')->default(false)->after('gone_url');
        });
    }

    public function down(): void
    {
        Schema::table('bio_pages', function (Blueprint $table) {
            $table->dropColumn(['gone_url', 'show_stats']);
        });
    }
};
