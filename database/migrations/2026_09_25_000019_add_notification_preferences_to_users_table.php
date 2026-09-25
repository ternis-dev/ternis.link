<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-user email preferences. In-app (database) notifications are
     * always delivered; these toggles gate the mail channel only.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notify_security_email')->default(true);
            $table->boolean('notify_admin_security_email')->default(true);
            $table->boolean('notify_server_error_email')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'notify_security_email',
                'notify_admin_security_email',
                'notify_server_error_email',
            ]);
        });
    }
};
