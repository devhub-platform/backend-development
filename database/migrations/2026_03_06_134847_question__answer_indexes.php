<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add composite indexes to fix timeout on Q&A queries.
 *
 * Problems solved:
 * - GET /questions?sort_by=recent     → needs (deleted_at, created_at)
 * - GET /questions?sort_by=views      → needs (deleted_at, views)
 * - GET /questions?is_resolved=true   → needs (deleted_at, is_resolved, created_at)
 * - GET /questions?sort_by=unanswered → needs (deleted_at, answers_count, created_at)
 * - GET /questions/{id}/answers       → needs (question_id, deleted_at, is_accepted, helpful_count)
 * - VoteService lockForUpdate         → needs (question_id, user_id) already unique ✅
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {

            // sort_by=recent → ORDER BY created_at DESC (most common query)
            if (!$this->indexExists('questions', 'questions_deleted_at_created_at_index')) {
                $table->index(['deleted_at', 'created_at'], 'questions_deleted_at_created_at_index');
            }

            // sort_by=views → ORDER BY views DESC
            if (!$this->indexExists('questions', 'questions_deleted_at_views_index')) {
                $table->index(['deleted_at', 'views'], 'questions_deleted_at_views_index');
            }

            // is_resolved filter + recent sort
            if (!$this->indexExists('questions', 'questions_resolved_created_at_index')) {
                $table->index(['deleted_at', 'is_resolved', 'created_at'], 'questions_resolved_created_at_index');
            }

            // sort_by=unanswered → WHERE answers_count = 0
            if (!$this->indexExists('questions', 'questions_unanswered_created_at_index')) {
                $table->index(['deleted_at', 'answers_count', 'created_at'], 'questions_unanswered_created_at_index');
            }

            // user questions → WHERE user_id = ? ORDER BY created_at DESC
            if (!$this->indexExists('questions', 'questions_user_id_created_at_index')) {
                $table->index(['user_id', 'deleted_at', 'created_at'], 'questions_user_id_created_at_index');
            }
        });

        Schema::table('answers', function (Blueprint $table) {

            // GET /questions/{id}/answers → ORDER BY is_accepted DESC, helpful_count DESC
            if (!$this->indexExists('answers', 'answers_question_sorting_index')) {
                $table->index(
                    ['question_id', 'deleted_at', 'is_accepted', 'helpful_count'],
                    'answers_question_sorting_index'
                );
            }

            // user answers → WHERE user_id = ? ORDER BY created_at DESC
            if (!$this->indexExists('answers', 'answers_user_id_created_at_index')) {
                $table->index(['user_id', 'deleted_at', 'created_at'], 'answers_user_id_created_at_index');
            }

            // accepted answers filter
            if (!$this->indexExists('answers', 'answers_user_accepted_index')) {
                $table->index(['user_id', 'is_accepted', 'deleted_at'], 'answers_user_accepted_index');
            }
        });

        Schema::table('question_votes', function (Blueprint $table) {

            // Load all votes for a question (eager loading)
            if (!$this->indexExists('question_votes', 'question_votes_question_type_index')) {
                $table->index(['question_id', 'vote_type'], 'question_votes_question_type_index');
            }
        });

        Schema::table('answer_votes', function (Blueprint $table) {

            // Load all votes for an answer (eager loading)
            if (!$this->indexExists('answer_votes', 'answer_votes_answer_type_index')) {
                $table->index(['answer_id', 'vote_type'], 'answer_votes_answer_type_index');
            }
        });

        Schema::table('question_views', function (Blueprint $table) {

            // trackView: WHERE question_id = ? AND user_id = ? (already unique but needs index)
            if (!$this->indexExists('question_views', 'question_views_question_user_index')) {
                $table->index(['question_id', 'user_id'], 'question_views_question_user_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex('questions_deleted_at_created_at_index');
            $table->dropIndex('questions_deleted_at_views_index');
            $table->dropIndex('questions_resolved_created_at_index');
            $table->dropIndex('questions_unanswered_created_at_index');
            $table->dropIndex('questions_user_id_created_at_index');
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->dropIndex('answers_question_sorting_index');
            $table->dropIndex('answers_user_id_created_at_index');
            $table->dropIndex('answers_user_accepted_index');
        });

        Schema::table('question_votes', function (Blueprint $table) {
            $table->dropIndex('question_votes_question_type_index');
        });

        Schema::table('answer_votes', function (Blueprint $table) {
            $table->dropIndex('answer_votes_answer_type_index');
        });

        Schema::table('question_views', function (Blueprint $table) {
            $table->dropIndex('question_views_question_user_index');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return collect(DB::select("SHOW INDEX FROM `{$table}`"))
            ->pluck('Key_name')
            ->contains($indexName);
    }
};
