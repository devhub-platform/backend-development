<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Safety migration for the `notifications` table:
 *
 * - If the table does NOT exist → creates the full table (mirrors Laravel's
 *   built-in `php artisan notifications:table` output).
 * - If the table already exists but is missing `read_at` → adds the column.
 * - If both table and column already exist → no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->json('data')->comment('Expected keys: title (string), body (string), url (string|null), type (info|success|warning|error)');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });

            return;
        }

        if (! Schema::hasColumn('notifications', 'read_at')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->timestamp('read_at')->nullable()->after('data');
            });
        }
    }

    public function down(): void
    {
        // We intentionally do not drop the table or column on rollback —
        // the table may contain user data that predates this plugin.
    }
};
