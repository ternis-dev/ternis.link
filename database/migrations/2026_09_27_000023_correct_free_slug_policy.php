<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('plans')
            ->where('name', 'free')
            ->update(['min_slug_length' => 5, 'slug_length_choice' => false]);
    }

    public function down(): void
    {
        DB::table('plans')
            ->where('name', 'free')
            ->update(['min_slug_length' => 6, 'slug_length_choice' => false]);
    }
};
