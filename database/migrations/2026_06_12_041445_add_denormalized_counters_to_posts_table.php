<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Only add columns that do not already exist.
            if (!Schema::hasColumn('posts', 'comments_count')) {
                $table->unsignedBigInteger('comments_count')->default(0)->after('views');
            }
            if (!Schema::hasColumn('posts', 'reactions_count')) {
                $table->unsignedBigInteger('reactions_count')->default(0)->after('comments_count');
            }
            if (!Schema::hasColumn('posts', 'saved_by_count')) {
                $table->unsignedBigInteger('saved_by_count')->default(0)->after('reactions_count');
            }
        });

        // Backfill — chunked to avoid locking the table for too long.
        DB::table('posts')->orderBy('id')->chunk(500, function ($posts) {
            foreach ($posts as $post) {
                DB::table('posts')->where('id', $post->id)->update([
                    'comments_count' => DB::table('comments')->where('post_id', $post->id)->count(),
                    'reactions_count' => DB::table('reactions')->where('reactable_id', $post->id)
                        ->where('reactable_type', \App\Models\Post::class)->count(),
                    'saved_by_count' => DB::table('saved_posts')->where('post_id', $post->id)->count(),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumnIfExists('comments_count');
            $table->dropColumnIfExists('reactions_count');
            $table->dropColumnIfExists('saved_by_count');
        });
    }
};
