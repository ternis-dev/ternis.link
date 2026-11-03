<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->string('bot_protection_level', 32)->nullable()->after('bot_protection');
        });

        Schema::table('link_groups', function (Blueprint $table) {
            $table->boolean('bot_protection')->nullable()->after('color');
            $table->string('bot_protection_level', 32)->nullable()->after('bot_protection');
        });
    }

    public function down(): void
    {
        Schema::table('link_groups', function (Blueprint $table) {
            $table->dropColumn(['bot_protection', 'bot_protection_level']);
        });

        Schema::table('links', function (Blueprint $table) {
            $table->dropColumn('bot_protection_level');
        });
    }
};
