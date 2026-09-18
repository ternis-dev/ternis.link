<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_id')->constrained('links')->cascadeOnDelete();
            $table->string('referrer', 2048)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('city', 255)->nullable();
            $table->boolean('is_direct_url')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['link_id', 'created_at']);
            $table->index('created_at');
            $table->index('is_direct_url');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clicks');
    }
};
