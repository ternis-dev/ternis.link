<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-user, per-table column preferences (visibility + order) for
     * the Livewire data tables. Shape:
     * {"<table-key>": {"hidden": ["domain"], "order": ["slug", ...]}}.
     * Missing keys fall back to each table's default column set.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('table_columns')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('table_columns');
        });
    }
};
