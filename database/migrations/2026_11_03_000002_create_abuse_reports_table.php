<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abuse_reports', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('link_id')->nullable()->constrained('links')->nullOnDelete();
            $table->string('reported_url', 2048);
            $table->string('category', 64)->index();
            $table->string('reporter_email', 255);
            $table->string('reporter_name', 255)->nullable();
            $table->text('reason');
            $table->string('status', 32)->default('pending')->index();
            $table->string('action_taken', 64)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignUlid('reviewed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abuse_reports');
    }
};
