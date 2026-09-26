<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin-side soft removal. Removed links stop resolving everywhere
     * (redirects, dashboards, API) but their rows stay for the public
     * stats pages and audit trail — nothing is ever hard-deleted.
     */
    public function up(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->boolean('is_removed')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->dropColumn('is_removed');
        });
    }
};
