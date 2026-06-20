<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('reported_post_id')->nullable()->after('reported_user_id')
                ->constrained('posts')->cascadeOnDelete();
            $table->string('type')->default('user')->after('reported_post_id'); // 'user' or 'post'
            $table->string('reason')->nullable()->after('message');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['reported_post_id']);
            $table->dropColumn(['reported_post_id', 'type', 'reason']);
        });
    }
};
