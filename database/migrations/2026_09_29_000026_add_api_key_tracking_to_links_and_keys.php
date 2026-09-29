<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Attribute links to the API key that created them and let keys
     * opt out of the main dashboard list.
     *
     * - links.api_key_id: nullable ULID FK -> api_keys.id, nullOnDelete.
     *   Dashboard-created links stay NULL (origin: dashboard). Links
     *   created with an SSO token also stay NULL (no key involved).
     *   The row survives key revocation/deletion; attribution is kept
     *   via activity_logs metadata as well.
     * - api_keys.show_on_dashboard: when false, links made with that
     *   key are hidden from the main dashboard list and live on a
     *   dedicated per-key page instead.
     */
    public function up(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->boolean('show_on_dashboard')->default(true)->after('name');
        });

        Schema::table('links', function (Blueprint $table) {
            $table->foreignUlid('api_key_id')->nullable()->after('user_id')
                ->constrained('api_keys')->nullOnDelete();
            $table->index(['api_key_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->dropForeign(['api_key_id']);
            $table->dropIndex(['api_key_id', 'created_at']);
            $table->dropColumn('api_key_id');
        });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropColumn('show_on_dashboard');
        });
    }
};
