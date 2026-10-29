<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allowed clients / pairs: allowlist rules for bypassing rate limits
     * and guest creation limits (e.g. for fallback services, internal bots).
     * Supports matching by:
     * - User-Agent + IP pair (both must match)
     * - IP only (exact or CIDR)
     * - User-Agent only (exact, pattern or substring)
     */
    public function up(): void
    {
        Schema::create('allowed_clients', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name', 120)->nullable()->comment('Descriptive label e.g. Twins on Ice Fallback');
            $table->string('ip_address', 255)->nullable()->comment('Exact IP, comma-separated IPs, or CIDR (e.g. 192.168.1.1, 10.0.0.0/8)');
            $table->string('user_agent', 512)->nullable()->comment('User-agent string, pattern or substring');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('is_active');
            $table->index('ip_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allowed_clients');
    }
};
