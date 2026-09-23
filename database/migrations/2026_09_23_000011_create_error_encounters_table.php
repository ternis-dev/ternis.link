<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Error encounters: one row per surfaced web/API exception so
     * production failures (stale SSO codes, token mismatches, 404
     * probes, 500s) stay inspectable without digging log files.
     * Only creator IP *hashes* are stored, never raw IPs.
     */
    public function up(): void
    {
        Schema::create('error_encounters', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('http_code')->nullable();
            $table->text('error_message')->nullable();
            $table->string('exception_class');
            $table->string('method', 10);
            $table->string('host');
            $table->string('path', 2048);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamps();

            $table->index(['http_code', 'created_at']);
            $table->index(['exception_class', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_encounters');
    }
};
