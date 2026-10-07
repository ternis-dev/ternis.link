<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add default domain and domain order preferences to users.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignUlid('default_domain_id')->nullable()->after('theme')
                ->constrained('domains')->nullOnDelete();
            $table->json('domain_order')->nullable()->after('default_domain_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['default_domain_id']);
            $table->dropColumn(['default_domain_id', 'domain_order']);
        });
    }
};
