<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ─── posts ────────────────────────────────────────────────────────────
        Schema::table('posts', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'posts_status_created_at');
            $table->index('views', 'posts_views');
        });

        // ─── comments ────────────────────────────────────────────────────────
        Schema::table('comments', function (Blueprint $table) {
            $table->index(['post_id', 'created_at'], 'comments_post_id_created_at');
        });

        // ─── reactions ────────────────────────────────────────────────────────
        Schema::table('reactions', function (Blueprint $table) {
            $table->index(['reactable_type', 'reactable_id'], 'reactions_reactable');
        });

        // ─── post_tags ────────────────────────────────────────────────────────
        Schema::table('post_tags', function (Blueprint $table) {
            $table->index(['tag_id', 'created_at'], 'post_tags_tag_id_created_at');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_status_created_at');
            $table->dropIndex('posts_views');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_post_id_created_at');
        });

        Schema::table('reactions', function (Blueprint $table) {
            $table->dropIndex('reactions_reactable');
        });

        Schema::table('post_tags', function (Blueprint $table) {
            $table->dropIndex('post_tags_tag_id_created_at');
        });
    }
};
