<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * User-action audit trail (actor, action, subject, metadata).
     * Rows are append-only: created_at only, never updated.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('actor_id')->nullable();
            $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();
            $table->string('action', 64);
            $table->nullableUlidMorphs('subject');
            $table->ulid('subject_owner_id')->nullable();
            $table->foreign('subject_owner_id')->references('id')->on('users')->nullOnDelete();
            $table->string('subject_label', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['actor_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['subject_owner_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
