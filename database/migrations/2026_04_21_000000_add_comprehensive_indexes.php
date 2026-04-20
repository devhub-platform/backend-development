<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {

        // ─────────────────────────────────────────────────────────────────
        // POSTS TABLE
        // ─────────────────────────────────────────────────────────────────
        $this->addIndexIfNotExists('posts', 'user_id', 'posts_user_id_index');
        $this->addIndexIfNotExists('posts', 'created_at', 'posts_created_at_index');
        $this->addIndexIfNotExists('posts', 'status', 'posts_status_index');
        $this->addCompositeIndexIfNotExists('posts', ['user_id', 'created_at'], 'posts_user_created_index');

        // ─────────────────────────────────────────────────────────────────
        // COMMENTS TABLE
        // ─────────────────────────────────────────────────────────────────
        $this->addIndexIfNotExists('comments', 'user_id', 'comments_user_id_index');
        $this->addIndexIfNotExists('comments', 'created_at', 'comments_created_at_index');
        $this->addIndexIfNotExists('comments', 'parent_id', 'comments_parent_id_index');
        $this->addCompositeIndexIfNotExists('comments', ['post_id', 'user_id'], 'comments_post_user_index');

        // ─────────────────────────────────────────────────────────────────
        // FOLLOWERS TABLE
        // ─────────────────────────────────────────────────────────────────
        $this->addIndexIfNotExists('followers', 'follower_id', 'followers_follower_id_index');
        $this->addIndexIfNotExists('followers', 'following_id', 'followers_following_id_index');
        $this->addIndexIfNotExists('followers', 'created_at', 'followers_created_at_index');

        // ─────────────────────────────────────────────────────────────────
        // POST_TAGS TABLE
        // ─────────────────────────────────────────────────────────────────
        $this->addIndexIfNotExists('post_tags', 'tag_id', 'post_tags_tag_id_index');
        $this->addCompositeIndexIfNotExists('post_tags', ['post_id', 'tag_id'], 'post_tags_post_tag_index');

        // ─────────────────────────────────────────────────────────────────
        // NOTIFICATIONS TABLE
        // ─────────────────────────────────────────────────────────────────
        $this->addCompositeIndexIfNotExists('notifications', ['notifiable_id', 'notifiable_type'], 'notifications_notifiable_index');
        $this->addIndexIfNotExists('notifications', 'created_at', 'notifications_created_at_index');
        $this->addIndexIfNotExists('notifications', 'read_at', 'notifications_read_at_index');

        // ─────────────────────────────────────────────────────────────────
        // SAVED_POSTS TABLE
        // ─────────────────────────────────────────────────────────────────
        $this->addIndexIfNotExists('saved_posts', 'user_id', 'saved_posts_user_id_index');

        // ─────────────────────────────────────────────────────────────────
        // REACTIONS TABLE
        // ─────────────────────────────────────────────────────────────────
        $this->addIndexIfNotExists('reactions', 'user_id', 'reactions_user_id_index');
        $this->addIndexIfNotExists('reactions', 'created_at', 'reactions_created_at_index');
        $this->addCompositeIndexIfNotExists('reactions', ['reactable_id', 'reactable_type'], 'reactions_reactable_index');

        // ─────────────────────────────────────────────────────────────────
        // TAG_USER TABLE
        // ─────────────────────────────────────────────────────────────────
        $this->addIndexIfNotExists('tag_user', 'tag_id', 'tag_user_tag_id_index');
        $this->addCompositeIndexIfNotExists('tag_user', ['user_id', 'tag_id'], 'tag_user_user_tag_index');

        // ─────────────────────────────────────────────────────────────────
        // SEARCH_HISTORIES TABLE
        // ─────────────────────────────────────────────────────────────────
        $this->addIndexIfNotExists('search_histories', 'created_at', 'search_histories_created_at_index');

        // ─────────────────────────────────────────────────────────────────
        // POST_VIEWS TABLE
        // ─────────────────────────────────────────────────────────────────
        $this->addIndexIfNotExists('post_views', 'created_at', 'post_views_created_at_index');

        // ─────────────────────────────────────────────────────────────────
        // ANSWERS TABLE
        // ─────────────────────────────────────────────────────────────────
        $this->addIndexIfNotExists('answers', 'user_id', 'answers_user_id_index');
        $this->addIndexIfNotExists('answers', 'created_at', 'answers_created_at_index');

        // ─────────────────────────────────────────────────────────────────
        // ANSWER_VOTES TABLE
        // ─────────────────────────────────────────────────────────────────
        $this->addIndexIfNotExists('answer_votes', 'user_id', 'answer_votes_user_id_index');

        // ─────────────────────────────────────────────────────────────────
        // AI_CHAT TABLES
        // ─────────────────────────────────────────────────────────────────
        $this->addIndexIfNotExists('ai_chat_sessions', 'created_at', 'ai_chat_sessions_created_at_index');
        $this->addCompositeIndexIfNotExists('ai_chat_messages', ['session_id', 'created_at'], 'ai_chat_messages_session_created_index');

        // ─────────────────────────────────────────────────────────────────
        // OTHER TABLES
        // ─────────────────────────────────────────────────────────────────
        $this->addIndexIfNotExists('reports', 'created_at', 'reports_created_at_index');
        $this->addIndexIfNotExists('reading_lists', 'created_at', 'reading_lists_created_at_index');
        $this->addIndexIfNotExists('reading_list_story', 'post_id', 'reading_list_story_post_id_index');
        $this->addIndexIfNotExists('user_statuses', 'created_at', 'user_statuses_created_at_index');
        $this->addIndexIfNotExists('feedbacks', 'created_at', 'feedbacks_created_at_index');
        $this->addCompositeIndexIfNotExists('generated_post_images', ['post_id', 'created_at'], 'generated_post_images_post_created_index');
        $this->addIndexIfNotExists('visits', 'created_at', 'visits_created_at_index');
        $this->addIndexIfNotExists('question_views', 'created_at', 'question_views_created_at_index');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexIfExists('posts', 'posts_user_id_index');
        $this->dropIndexIfExists('posts', 'posts_created_at_index');
        $this->dropIndexIfExists('posts', 'posts_status_index');
        $this->dropIndexIfExists('posts', 'posts_user_created_index');

        $this->dropIndexIfExists('comments', 'comments_user_id_index');
        $this->dropIndexIfExists('comments', 'comments_created_at_index');
        $this->dropIndexIfExists('comments', 'comments_parent_id_index');
        $this->dropIndexIfExists('comments', 'comments_post_user_index');

        $this->dropIndexIfExists('followers', 'followers_follower_id_index');
        $this->dropIndexIfExists('followers', 'followers_following_id_index');
        $this->dropIndexIfExists('followers', 'followers_created_at_index');

        $this->dropIndexIfExists('post_tags', 'post_tags_tag_id_index');
        $this->dropIndexIfExists('post_tags', 'post_tags_post_tag_index');

        $this->dropIndexIfExists('notifications', 'notifications_notifiable_index');
        $this->dropIndexIfExists('notifications', 'notifications_created_at_index');
        $this->dropIndexIfExists('notifications', 'notifications_read_at_index');

        $this->dropIndexIfExists('saved_posts', 'saved_posts_user_id_index');

        $this->dropIndexIfExists('reactions', 'reactions_user_id_index');
        $this->dropIndexIfExists('reactions', 'reactions_created_at_index');
        $this->dropIndexIfExists('reactions', 'reactions_reactable_index');

        $this->dropIndexIfExists('tag_user', 'tag_user_tag_id_index');
        $this->dropIndexIfExists('tag_user', 'tag_user_user_tag_index');

        $this->dropIndexIfExists('search_histories', 'search_histories_created_at_index');
        $this->dropIndexIfExists('post_views', 'post_views_created_at_index');

        $this->dropIndexIfExists('answers', 'answers_user_id_index');
        $this->dropIndexIfExists('answers', 'answers_created_at_index');

        $this->dropIndexIfExists('answer_votes', 'answer_votes_user_id_index');

        $this->dropIndexIfExists('ai_chat_sessions', 'ai_chat_sessions_created_at_index');
        $this->dropIndexIfExists('ai_chat_messages', 'ai_chat_messages_session_created_index');

        $this->dropIndexIfExists('reports', 'reports_created_at_index');
        $this->dropIndexIfExists('reading_lists', 'reading_lists_created_at_index');
        $this->dropIndexIfExists('reading_list_story', 'reading_list_story_post_id_index');

        $this->dropIndexIfExists('user_statuses', 'user_statuses_created_at_index');
        $this->dropIndexIfExists('feedbacks', 'feedbacks_created_at_index');

        $this->dropIndexIfExists('generated_post_images', 'generated_post_images_post_created_index');
        $this->dropIndexIfExists('visits', 'visits_created_at_index');
        $this->dropIndexIfExists('question_views', 'question_views_created_at_index');
    }

    /**
     * Add a single column index if it doesn't exist
     */
    private function addIndexIfNotExists(string $table, string $column, string $indexName): void
    {
        try {
            if (!$this->indexExists($table, $indexName)) {
                Schema::table($table, function (Blueprint $table) use ($column) {
                    $table->index($column);
                });
            }
        } catch (\Exception $e) {
            // Index might already exist or table doesn't exist, skip silently
        }
    }

    /**
     * Add a composite index if it doesn't exist
     */
    private function addCompositeIndexIfNotExists(string $table, array $columns, string $indexName): void
    {
        try {
            if (!$this->indexExists($table, $indexName)) {
                Schema::table($table, function (Blueprint $table) use ($columns) {
                    $table->index($columns);
                });
            }
        } catch (\Exception $e) {
            // Index might already exist or table doesn't exist, skip silently
        }
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $indexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes($table);
            return isset($indexes[strtolower($indexName)]) || isset($indexes[$indexName]);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Drop an index if it exists
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        try {
            if ($this->indexExists($table, $indexName)) {
                Schema::table($table, function (Blueprint $table) use ($indexName) {
                    $table->dropIndex($indexName);
                });
            }
        } catch (\Exception $e) {
            // Index doesn't exist or error occurred, silently continue
        }
    }
};

