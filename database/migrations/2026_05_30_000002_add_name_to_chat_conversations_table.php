<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Musonza\Chat\ConfigurationManager;

return new class extends Migration {
    public function up(): void
    {
        $tableName = ConfigurationManager::CONVERSATIONS_TABLE;

        if (!Schema::hasColumn($tableName, 'name')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('name')->nullable()->after('data');
            });
        }
    }

    public function down(): void
    {
        $tableName = ConfigurationManager::CONVERSATIONS_TABLE;

        if (Schema::hasColumn($tableName, 'name')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }
    }
};