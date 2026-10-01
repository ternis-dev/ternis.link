<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bio_pages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('domain_id')->nullable()->constrained('domains')->nullOnDelete();
            $table->foreignUlid('parent_id')->nullable()->constrained('bio_pages')->cascadeOnDelete();
            $table->string('slug', 64)->default('-');
            $table->string('title', 80);
            $table->string('bio', 280)->nullable();
            $table->string('avatar_url', 2048)->nullable();
            $table->string('theme', 16)->default('minimal');
            $table->string('accent', 7)->nullable();
            $table->string('og_title', 120)->nullable();
            $table->string('og_description', 300)->nullable();
            $table->string('og_image_url', 2048)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_removed')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->bigInteger('view_count')->default(0);
            $table->timestamps();

            $table->index('domain_id');
            $table->index(['parent_id', 'slug']);
            $table->index('user_id');
        });

        Schema::create('bio_buttons', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('bio_page_id')->constrained('bio_pages')->cascadeOnDelete();
            $table->string('label', 60);
            $table->string('sublabel', 120)->nullable();
            $table->string('kind', 16)->default('link');
            $table->string('destination_url', 2048)->nullable();
            $table->string('icon', 32)->nullable();
            $table->string('thumbnail_url', 2048)->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->bigInteger('tap_count')->default(0);
            $table->timestamps();

            $table->index(['bio_page_id', 'sort_order']);
        });

        Schema::create('bio_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('bio_page_id')->constrained('bio_pages')->cascadeOnDelete();
            $table->foreignUlid('bio_button_id')->nullable()->constrained('bio_buttons')->nullOnDelete();
            $table->string('kind', 8);
            $table->string('referrer', 2048)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('city', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['bio_page_id', 'created_at']);
            $table->index(['bio_button_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bio_events');
        Schema::dropIfExists('bio_buttons');
        Schema::dropIfExists('bio_pages');
    }
};
