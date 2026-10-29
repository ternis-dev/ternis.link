<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('threat_evaluations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('link_id')->nullable()->constrained('links')->nullOnDelete();
            $table->foreignUlid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('creator_ip_hash', 64)->index();
            $table->string('destination_url', 2048);
            $table->string('destination_host', 255)->index();
            $table->text('user_agent')->nullable();
            $table->string('evaluation_type', 40)->index();
            $table->string('verdict', 40)->index();
            $table->unsignedTinyInteger('threat_score');
            $table->decimal('confidence', 4, 3);
            $table->string('action_taken', 40)->index();
            $table->text('reasoning')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['created_at', 'verdict']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('threat_evaluations');
    }
};
