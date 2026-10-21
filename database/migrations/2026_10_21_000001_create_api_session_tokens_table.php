<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Short-lived tokens minted by the CORS-protected auth API of the
 * public dashboard. They let a cross-origin client (an allowlisted
 * web app) act on behalf of the signed-in user without ever seeing a
 * long-lived API key.
 *
 * Only the SHA-256 digest of the token is stored; the raw value is
 * returned exactly once.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_session_tokens', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('token_prefix', 16);
            $table->string('origin', 255)->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_session_tokens');
    }
};
