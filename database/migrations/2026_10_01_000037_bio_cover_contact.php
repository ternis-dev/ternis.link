<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bio_pages', function (Blueprint $table) {
            $table->string('cover_url', 2048)->nullable()->after('avatar_url');
            $table->string('footer_text', 140)->nullable()->after('theme_color');
        });

        Schema::table('bio_buttons', function (Blueprint $table) {
            $table->string('contact_email', 255)->nullable()->after('modal_image_url');
            $table->string('contact_phone', 40)->nullable()->after('contact_email');
        });
    }

    public function down(): void
    {
        Schema::table('bio_pages', function (Blueprint $table) {
            $table->dropColumn(['cover_url', 'footer_text']);
        });

        Schema::table('bio_buttons', function (Blueprint $table) {
            $table->dropColumn(['contact_email', 'contact_phone']);
        });
    }
};
