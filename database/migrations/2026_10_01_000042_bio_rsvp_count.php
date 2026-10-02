<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bio_buttons', function (Blueprint $table) {
            $table->bigInteger('rsvp_count')->default(0)->after('tap_count');
        });

        Schema::table('bio_events', function (Blueprint $table) {
            $table->index(['bio_button_id', 'ip_hash']);
        });
    }

    public function down(): void
    {
        Schema::table('bio_buttons', function (Blueprint $table) {
            $table->dropColumn('rsvp_count');
        });

        Schema::table('bio_events', function (Blueprint $table) {
            $table->dropIndex(['bio_button_id', 'ip_hash']);
        });
    }
};
