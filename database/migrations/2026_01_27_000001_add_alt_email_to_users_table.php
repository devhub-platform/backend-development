<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('alt_email')->nullable()->unique()->after('email');
            $table->timestamp('alt_email_verified_at')->nullable()->after('alt_email');
            $table->string('alt_email_otp')->nullable()->after('alt_email_verified_at');
            $table->timestamp('alt_email_otp_expires_at')->nullable()->after('alt_email_otp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['alt_email', 'alt_email_verified_at', 'alt_email_otp', 'alt_email_otp_expires_at']);
        });
    }
};
