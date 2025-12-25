<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('linkedin_username')->nullable()->after('pronouns');
            $table->string('github_username')->nullable()->after('provider_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('linkedin_username');
            $table->dropColumn('github_username');
        });
    }
};
