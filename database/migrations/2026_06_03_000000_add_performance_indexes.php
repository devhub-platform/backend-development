<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add indexes for faster query execution
        Schema::table('posts', function (Blueprint $table) {
            if (!Schema::hasIndex('posts', 'posts_status_created_at_index')) {
                $table->index(['status', 'created_at']);
            }

            if (!Schema::hasIndex('posts', 'posts_user_id_status_index')) {
                $table->index(['user_id', 'status']);
            }

            if (!Schema::hasIndex('posts', 'posts_views_created_at_index')) {
                $table->index(['views', 'created_at']);
            }
        });

        Schema::table('followers', function (Blueprint $table) {
            if (!Schema::hasIndex('followers', 'followers_follower_id_index')) {
                $table->index('follower_id');
            }

            if (!Schema::hasIndex('followers', 'followers_following_id_index')) {
                $table->index('following_id');
            }
        });

        Schema::table('post_tags', function (Blueprint $table) {
            if (!Schema::hasIndex('post_tags', 'post_tags_tag_id_index')) {
                $table->index('tag_id');
            }

            if (!Schema::hasIndex('post_tags', 'post_tags_post_id_index')) {
                $table->index('post_id');
            }
        });

        Schema::table('post_views', function (Blueprint $table) {
            if (!Schema::hasIndex('post_views', 'post_views_user_id_viewed_at_index')) {
                $table->index(['user_id', 'viewed_at']);
            }

            // Index for post views
            if (!Schema::hasIndex('post_views', 'post_views_post_id_index')) {
                $table->index('post_id');
            }
        });

        Schema::table('reactions', function (Blueprint $table) {
            // Index for reactions count queries
            if (!Schema::hasIndex('reactions', 'reactions_reactable_type_reactable_id_index')) {
                $table->index(['reactable_type', 'reactable_id']);
            }
        });

        Schema::table('comments', function (Blueprint $table) {
            // Index for post comments
            if (!Schema::hasIndex('comments', 'comments_post_id_index')) {
                $table->index('post_id');
            }

            // Index for user comments
            if (!Schema::hasIndex('comments', 'comments_user_id_index')) {
                $table->index('user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_status_created_at_index');
            $table->dropIndex('posts_user_id_status_index');
            $table->dropIndex('posts_views_created_at_index');
        });

        Schema::table('followers', function (Blueprint $table) {
            $table->dropIndex('followers_follower_id_index');
            $table->dropIndex('followers_following_id_index');
        });

        Schema::table('post_tags', function (Blueprint $table) {
            $table->dropIndex('post_tags_tag_id_index');
            $table->dropIndex('post_tags_post_id_index');
        });

        Schema::table('post_views', function (Blueprint $table) {
            $table->dropIndex('post_views_user_id_viewed_at_index');
            $table->dropIndex('post_views_post_id_index');
        });

        Schema::table('reactions', function (Blueprint $table) {
            $table->dropIndex('reactions_reactable_type_reactable_id_index');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_post_id_index');
            $table->dropIndex('comments_user_id_index');
        });
    }


};
