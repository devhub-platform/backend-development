<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ─── comments ──────────────────────────────────────
        Schema::table('comments', function (Blueprint $table) {
            // Add simple index for faster counts
            $table->index('post_id', 'comments_post_id');
        });

        // ─── reactions ─────────────────────────────────────
        Schema::table('reactions', function (Blueprint $table) {
            // Reverse index for polymorphic queries optimization
            $table->index(['reactable_id', 'reactable_type'], 'reactions_reactable_reverse');
        });

        // ─── post_tags ─────────────────────────────────────
        Schema::table('post_tags', function (Blueprint $table) {
            // Faster tag → posts lookup
            $table->index(['tag_id', 'post_id'], 'post_tags_tag_post');
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_post_id');
        });

        Schema::table('reactions', function (Blueprint $table) {
            $table->dropIndex('reactions_reactable_reverse');
        });

        Schema::table('post_tags', function (Blueprint $table) {
            $table->dropIndex('post_tags_tag_post');
        });
    }
};
