<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('link_groups', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('parent_id')->nullable()->constrained('link_groups')->nullOnDelete();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->string('color', 7)->nullable()->comment('Hex color for visual identification');
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_removed')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'parent_id']);
            $table->index(['user_id', 'is_removed']);
            $table->index(['parent_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_groups');
    }
};
