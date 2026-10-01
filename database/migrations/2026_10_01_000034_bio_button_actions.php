<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bio_buttons', function (Blueprint $table) {
            $table->string('action', 8)->default('url')->after('kind');
            $table->foreignUlid('target_page_id')->nullable()->after('destination_url')->constrained('bio_pages')->nullOnDelete();
            $table->string('modal_title', 80)->nullable()->after('target_page_id');
            $table->string('modal_body', 1000)->nullable()->after('modal_title');
            $table->string('modal_image_url', 2048)->nullable()->after('modal_body');

            $table->index('target_page_id');
        });
    }

    public function down(): void
    {
        Schema::table('bio_buttons', function (Blueprint $table) {
            $table->dropForeign(['target_page_id']);
            $table->dropIndex(['target_page_id']);
            $table->dropColumn(['action', 'target_page_id', 'modal_title', 'modal_body', 'modal_image_url']);
        });
    }
};
