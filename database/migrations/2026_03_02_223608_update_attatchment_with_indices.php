<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add indexes to the attachments table to eliminate full table scans.
 *
 * Without these, every query on attachments performs a full scan across
 * all users' files — causing timeouts as the table grows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attachments', function (Blueprint $table) {

            // Most queries filter by user_id first — this is the most important index
            if (!$this->indexExists('attachments', 'attachments_user_id_index')) {
                $table->index('user_id', 'attachments_user_id_index');
            }

            // ChatService queries: WHERE id IN (...) AND user_id = ?
            if (!$this->indexExists('attachments', 'attachments_user_id_status_index')) {
                $table->index(['user_id', 'status'], 'attachments_user_id_status_index');
            }

            // CleanupChatData queries: WHERE status = 'failed' AND created_at < ?
            if (!$this->indexExists('attachments', 'attachments_status_created_at_index')) {
                $table->index(['status', 'created_at'], 'attachments_status_created_at_index');
            }

            // session_id lookups when deleting a session's attachments
            if (!$this->indexExists('attachments', 'attachments_session_id_index')) {
                $table->index('session_id', 'attachments_session_id_index');
            }
        });

        Schema::table('ai_chat_sessions', function (Blueprint $table) {

            // HistoryController: WHERE user_id = ? ORDER BY pinned DESC, updated_at DESC
            if (!$this->indexExists('ai_chat_sessions', 'sessions_user_id_pinned_updated_index')) {
                $table->index(['user_id', 'pinned', 'updated_at'], 'sessions_user_id_pinned_updated_index');
            }
        });

        Schema::table('ai_chat_messages', function (Blueprint $table) {

            // Every message fetch: WHERE ai_chat_session_id = ? ORDER BY created_at
            if (!$this->indexExists('ai_chat_messages', 'messages_session_id_created_at_index')) {
                $table->index(['ai_chat_session_id', 'created_at'], 'messages_session_id_created_at_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropIndexIfExists('attachments_user_id_index');
            $table->dropIndexIfExists('attachments_user_id_status_index');
            $table->dropIndexIfExists('attachments_status_created_at_index');
            $table->dropIndexIfExists('attachments_session_id_index');
        });

        Schema::table('ai_chat_sessions', function (Blueprint $table) {
            $table->dropIndexIfExists('sessions_user_id_pinned_updated_index');
        });

        Schema::table('ai_chat_messages', function (Blueprint $table) {
            $table->dropIndexIfExists('messages_session_id_created_at_index');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return collect(\Illuminate\Support\Facades\DB::select("SHOW INDEX FROM `{$table}`"))
            ->pluck('Key_name')
            ->contains($indexName);
    }
};
