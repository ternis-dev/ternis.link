<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('link_targets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('link_id')->constrained('links')->cascadeOnDelete();
            $table->string('label', 60)->nullable();
            $table->string('destination_url', 2048);
            $table->json('country_codes')->nullable();
            $table->string('device', 16)->nullable();
            $table->unsignedSmallInteger('weight')->default(100);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->bigInteger('click_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['link_id', 'sort_order']);
            $table->index(['link_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_targets');
    }
};
