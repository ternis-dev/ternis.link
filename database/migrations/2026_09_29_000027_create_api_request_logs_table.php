<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-request API audit trail: one row per /v1/* request.
     *
     * Privacy by design (same rules as error_encounters): only a
     * one-way IP hash is stored, never the raw address; user agents
     * are truncated to 512 chars; no request bodies, query strings,
     * or tokens are persisted. Retention is 90 days (see
     * privacy:prune-api-logs); aggregates are not kept.
     */
    public function up(): void
    {
        Schema::create('api_request_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('api_key_id')->nullable()->constrained('api_keys')->nullOnDelete();
            $table->string('method', 10);
            $table->string('host', 255);
            $table->string('path', 2048);
            $table->unsignedSmallInteger('status')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['api_key_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_request_logs');
    }
};
