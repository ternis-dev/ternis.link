<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->foreignUlid('preferred_domain_id')
                ->nullable()
                ->after('api_version')
                ->constrained('domains')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropForeign(['preferred_domain_id']);
            $table->dropColumn('preferred_domain_id');
        });
    }
};
