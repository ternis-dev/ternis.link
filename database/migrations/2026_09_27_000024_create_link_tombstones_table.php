<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stats ledger for hard-deleted links. When an admin permanently
     * deletes a link, the row and its click details (IPs, referrers,
     * user agents) are destroyed — but every creation must stay
     * counted. The tombstone keeps only aggregates: no slugs, no
     * destinations, no personal data. NetworkStats merges these rows
     * into the public totals.
     */
    public function up(): void
    {
        Schema::create('link_tombstones', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('domain_id')->nullable()->constrained('domains')->nullOnDelete();
            $table->string('domain_hostname', 255);
            $table->date('created_day');
            $table->unsignedInteger('click_count')->default(0);
            $table->json('clicks_by_day')->nullable();
            $table->timestamps();

            $table->index(['created_day']);
            $table->index(['domain_hostname']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_tombstones');
    }
};
