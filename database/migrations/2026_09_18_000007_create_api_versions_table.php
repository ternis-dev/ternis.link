<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_versions', function (Blueprint $table) {
            $table->unsignedInteger('version')->primary();
            $table->string('status')->default('active'); // active, deprecated, retired
            $table->date('deprecated_at')->nullable();
            $table->text('changelog')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_versions');
    }
};
