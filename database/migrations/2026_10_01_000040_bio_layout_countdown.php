<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bio_pages', function (Blueprint $table) {
            $table->string('layout', 8)->default('list')->after('button_style');
            $table->boolean('hide_branding')->default(false)->after('layout');
            $table->string('password_hint', 120)->nullable()->after('password_hash');
        });

        Schema::table('bio_buttons', function (Blueprint $table) {
            $table->string('badge', 12)->nullable()->after('open_new');
            $table->timestamp('event_at')->nullable()->after('badge');
        });
    }

    public function down(): void
    {
        Schema::table('bio_pages', function (Blueprint $table) {
            $table->dropColumn(['layout', 'hide_branding', 'password_hint']);
        });

        Schema::table('bio_buttons', function (Blueprint $table) {
            $table->dropColumn(['badge', 'event_at']);
        });
    }
};
