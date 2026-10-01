<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->string('og_title', 120)->nullable()->after('description');
            $table->string('og_description', 300)->nullable()->after('og_title');
            $table->string('og_image_url', 2048)->nullable()->after('og_description');
        });
    }

    public function down(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->dropColumn(['og_title', 'og_description', 'og_image_url']);
        });
    }
};
