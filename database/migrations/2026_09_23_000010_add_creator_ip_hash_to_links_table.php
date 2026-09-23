<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('links', function (Blueprint $table) {
            // SHA-256 of the creator IP for anonymous (guest) links.
            // Authenticated links stay attributed via user_id instead.
            // Hashed, never the raw IP — same privacy approach as clicks.ip_hash.
            $table->string('creator_ip_hash', 64)->nullable()->after('user_id');
            $table->index(['creator_ip_hash', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->dropIndex(['creator_ip_hash', 'created_at']);
            $table->dropColumn('creator_ip_hash');
        });
    }
};
