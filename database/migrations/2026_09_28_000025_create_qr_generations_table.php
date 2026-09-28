<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * QR-code generations. Counts only: format + optional link
     * reference + timestamp. No URLs, no IPs, no user agents — a
     * rendered QR code carries no personal data worth keeping.
     * link_id nulls out when the link row is hard-deleted; the
     * count itself always survives.
     */
    public function up(): void
    {
        Schema::create('qr_generations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('link_id')->nullable()->constrained('links')->nullOnDelete();
            $table->string('format', 8)->default('png');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['created_at']);
            $table->index(['link_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_generations');
    }
};
