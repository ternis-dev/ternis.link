<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->boolean('user_tracking_enabled')->default(false)->after('utm_campaign');
        });

        Schema::table('clicks', function (Blueprint $table) {
            $table->json('query_params')->nullable()->after('is_direct_url');
            $table->json('tags')->nullable()->after('query_params');
            $table->string('user_identifier', 255)->nullable()->after('tags');
            $table->index(['link_id', 'user_identifier']);
        });
    }

    public function down(): void
    {
        Schema::table('clicks', function (Blueprint $table) {
            $table->dropIndex(['link_id', 'user_identifier']);
            $table->dropColumn(['query_params', 'tags', 'user_identifier']);
        });

        Schema::table('links', function (Blueprint $table) {
            $table->dropColumn('user_tracking_enabled');
        });
    }
};
