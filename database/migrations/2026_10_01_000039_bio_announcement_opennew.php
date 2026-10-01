<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bio_pages', function (Blueprint $table) {
            $table->string('announcement_text', 140)->nullable()->after('footer_text');
            $table->string('announcement_url', 2048)->nullable()->after('announcement_text');
        });

        Schema::table('bio_buttons', function (Blueprint $table) {
            $table->boolean('open_new')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('bio_pages', function (Blueprint $table) {
            $table->dropColumn(['announcement_text', 'announcement_url']);
        });

        Schema::table('bio_buttons', function (Blueprint $table) {
            $table->dropColumn('open_new');
        });
    }
};
