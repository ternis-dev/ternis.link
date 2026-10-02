<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bio_buttons', function (Blueprint $table) {
            $table->boolean('download_file')->default(false)->after('open_new');
        });
    }

    public function down(): void
    {
        Schema::table('bio_buttons', function (Blueprint $table) {
            $table->dropColumn('download_file');
        });
    }
};
