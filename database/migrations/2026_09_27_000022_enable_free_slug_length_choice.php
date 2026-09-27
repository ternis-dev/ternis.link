<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('plans')
            ->where('name', 'free')
            ->update(['slug_length_choice' => true]);
    }

    public function down(): void
    {
        DB::table('plans')
            ->where('name', 'free')
            ->update(['slug_length_choice' => false]);
    }
};
