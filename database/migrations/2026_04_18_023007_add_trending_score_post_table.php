<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ───────────── POSTS ─────────────
        Schema::table('posts', function (Blueprint $table) {

            if (!Schema::hasColumn('posts', 'trending_score')) {
                $table->float('trending_score')->default(0)->after('content');
            }

        });

        $this->safeIndex('posts', 'posts_status_trending_score', function ($table) {
            $table->index(['status', 'trending_score'], 'posts_status_trending_score');
        });

        $this->safeIndex('posts', 'posts_status_embedded_at', function ($table) {
            $table->index(['status', 'embedded_at'], 'posts_status_embedded_at');
        });

        // ───────────── COMMENTS ─────────────
        $this->safeIndex('comments', 'comments_post_id', function ($table) {
            $table->index('post_id', 'comments_post_id');
        });

        $this->safeIndex('comments', 'comments_post_id_created_at', function ($table) {
            $table->index(['post_id', 'created_at'], 'comments_post_id_created_at');
        });

        // ───────────── REACTIONS ─────────────
        $this->safeIndex('reactions', 'reactions_reactable', function ($table) {
            $table->index(['reactable_type', 'reactable_id'], 'reactions_reactable');
        });

        $this->safeIndex('reactions', 'reactions_reactable_reverse', function ($table) {
            $table->index(['reactable_id', 'reactable_type'], 'reactions_reactable_reverse');
        });

        // ───────────── POST TAGS ─────────────
        $this->safeIndex('post_tags', 'post_tags_tag_post', function ($table) {
            $table->index(['tag_id', 'post_id'], 'post_tags_tag_post');
        });

        $this->safeIndex('post_tags', 'post_tags_tag_id_created_at', function ($table) {
            $table->index(['tag_id', 'created_at'], 'post_tags_tag_id_created_at');
        });
    }

    private function safeIndex(string $tableName, string $indexName, callable $callback): void
    {
        $exists = DB::select("
            SHOW INDEX FROM {$tableName}
            WHERE Key_name = '{$indexName}'
        ");

        Schema::table($tableName, function (Blueprint $table) use ($callback, $exists) {
            if (!empty($exists)) {
                try {
                    $table->dropIndex($exists[0]->Key_name);
                } catch (\Throwable $e) {}
            }

            $callback($table);
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_status_trending_score');
            $table->dropIndex('posts_status_embedded_at');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_post_id');
            $table->dropIndex('comments_post_id_created_at');
        });

        Schema::table('reactions', function (Blueprint $table) {
            $table->dropIndex('reactions_reactable');
            $table->dropIndex('reactions_reactable_reverse');
        });

        Schema::table('post_tags', function (Blueprint $table) {
            $table->dropIndex('post_tags_tag_post');
            $table->dropIndex('post_tags_tag_id_created_at');
        });
    }
};
