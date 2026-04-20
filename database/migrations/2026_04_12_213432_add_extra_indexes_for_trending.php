<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add trending_score column only if it doesn't exist yet
        Schema::table('posts', function (Blueprint $table) {
            if (!Schema::hasColumn('posts', 'trending_score')) {
                $table->float('trending_score')->default(0)->after('content');
            }
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (Schema::hasColumn('posts', 'trending_score')) {
                $table->dropColumn('trending_score');
            }
        });
    }
};
