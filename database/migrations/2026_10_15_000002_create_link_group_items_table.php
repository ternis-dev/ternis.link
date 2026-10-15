<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('link_group_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('link_group_id')->constrained('link_groups')->cascadeOnDelete();
            $table->foreignUlid('link_id')->constrained('links')->cascadeOnDelete();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamp('created_at');

            $table->unique(['link_group_id', 'link_id']);
            $table->index(['link_id', 'link_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_group_items');
    }
};
