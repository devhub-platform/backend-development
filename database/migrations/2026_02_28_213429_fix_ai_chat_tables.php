<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * -------------------------------------------------
         * Attachments Table
         * -------------------------------------------------
         */
        Schema::table('attachments', function (Blueprint $table) {

            if (!Schema::hasColumn('attachments', 'size')) {
                $table->unsignedBigInteger('size')
                    ->default(0)
                    ->after('mime_type');
            }

            if (!Schema::hasColumn('attachments', 'extension')) {
                $table->string('extension', 20)
                    ->nullable()
                    ->after('size');
            }

            if (!Schema::hasColumn('attachments', 's3_path')) {
                $table->string('s3_path')
                    ->nullable()
                    ->after('url');
            }

            if (!Schema::hasColumn('attachments', 'text')) {
                $table->longText('text')
                    ->nullable()
                    ->after('s3_path');
            }
        });

        /**
         * -------------------------------------------------
         * AI Chat Sessions Table
         * -------------------------------------------------
         */
        Schema::table('ai_chat_sessions', function (Blueprint $table) {

            if (!Schema::hasColumn('ai_chat_sessions', 'pinned')) {
                $table->boolean('pinned')
                    ->default(false)
                    ->after('model');
            }

            if (!Schema::hasColumn('ai_chat_sessions', 'active')) {
                $table->boolean('active')
                    ->default(true)
                    ->after('pinned');
            }

            if (!Schema::hasColumn('ai_chat_sessions', 'closed_at')) {
                $table->timestamp('closed_at')
                    ->nullable()
                    ->after('active');
            }
        });

        /**
         * -------------------------------------------------
         * AI Chat Messages Table
         * -------------------------------------------------
         */
        Schema::table('ai_chat_messages', function (Blueprint $table) {

            if (Schema::hasColumn('ai_chat_messages', 'attachments')) {
                $table->json('attachments')
                    ->nullable()
                    ->default(null)
                    ->change();
            } else {
                $table->json('attachments')
                    ->nullable()
                    ->after('content');
            }
        });
    }

    public function down(): void
    {
        /**
         * -------------------------------------------------
         * Attachments Table Rollback
         * -------------------------------------------------
         */
        Schema::table('attachments', function (Blueprint $table) {

            if (Schema::hasColumn('attachments', 'size')) {
                $table->dropColumn('size');
            }

            if (Schema::hasColumn('attachments', 'extension')) {
                $table->dropColumn('extension');
            }

            if (Schema::hasColumn('attachments', 's3_path')) {
                $table->dropColumn('s3_path');
            }

            if (Schema::hasColumn('attachments', 'text')) {
                $table->dropColumn('text');
            }
        });

        /**
         * -------------------------------------------------
         * AI Chat Sessions Table Rollback
         * -------------------------------------------------
         */
        Schema::table('ai_chat_sessions', function (Blueprint $table) {

            if (Schema::hasColumn('ai_chat_sessions', 'pinned')) {
                $table->dropColumn('pinned');
            }

            if (Schema::hasColumn('ai_chat_sessions', 'active')) {
                $table->dropColumn('active');
            }

            if (Schema::hasColumn('ai_chat_sessions', 'closed_at')) {
                $table->dropColumn('closed_at');
            }
        });
    }
};
