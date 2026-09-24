<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clicks', function (Blueprint $table) {
            $table->text('ip_encrypted')->nullable()->after('ip_hash');
        });

        Schema::table('links', function (Blueprint $table) {
            $table->text('creator_ip_encrypted')->nullable()->after('creator_ip_hash');
        });
    }

    public function down(): void
    {
        Schema::table('clicks', function (Blueprint $table) {
            $table->dropColumn('ip_encrypted');
        });

        Schema::table('links', function (Blueprint $table) {
            $table->dropColumn('creator_ip_encrypted');
        });
    }
};
