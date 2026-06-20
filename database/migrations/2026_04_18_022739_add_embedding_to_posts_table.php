<?php

// ============================================================
//  MIGRATION 1: Add embedding column to posts table
//  File: database/migrations/YYYY_MM_DD_add_embedding_to_posts_table.php
// ============================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * WHY JSON and not a dedicated vector type:
     *   MySQL has no native vector type (until very recently, and not widely
     *   available). JSON is the correct storage format for a float array that
     *   is read by PHP and never queried with SQL operators. Comparisons are
     *   done in PHP via EmbeddingService::cosine(), not in SQL.
     *
     * WHY nullable:
     *   Posts are saved first, then the embedding job runs asynchronously.
     *   Null = "not yet embedded" — the feed degrades gracefully for these posts.
     *
     * Performance note:
     *   A JSON column of ~4000 floats (Qwen3-8B dimension) is ~32 KB per row.
     *   For a table with 100k posts this adds ~3 GB storage. Consider archiving
     *   old post embeddings if storage is a concern.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Stores the full embedding vector as a JSON float array
            $table->json('embedding')->nullable()->after('content');

            // Track when the embedding was last generated — useful for
            // re-embedding jobs when the model changes
            $table->timestamp('embedded_at')->nullable()->after('embedding');

            // Index on embedded_at to efficiently find posts needing (re-)embedding
            $table->index('embedded_at', 'posts_embedded_at');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_embedded_at');
            $table->dropColumn(['embedding', 'embedded_at']);
        });
    }
};


// ============================================================
//  Post model cast — add this to App\Models\Post
// ============================================================

/*
    // In your Post model's $casts array:
    protected $casts = [
        // ... existing casts ...
        'embedding'   => 'array',      // auto JSON encode/decode
        'embedded_at' => 'datetime',
    ];
*/


// ============================================================
//  MIGRATION 2: Artisan command for bulk back-filling embeddings
//  File: app/Console/Commands/BackfillPostEmbeddings.php
//  Run once after deploying: php artisan posts:backfill-embeddings
// ============================================================

/*
namespace App\Console\Commands;

use App\Jobs\GeneratePostEmbeddingJob;
use App\Models\Post;
use Illuminate\Console\Command;

class BackfillPostEmbeddings extends Command
{
    protected $signature   = 'posts:backfill-embeddings {--limit=500}';
    protected $description = 'Dispatch embedding jobs for all published posts missing an embedding';

    public function handle(): void
    {
        $limit = (int) $this->option('limit');

        $posts = Post::query()
            ->where('status', 'published')
            ->whereNull('embedding')
            ->latest()
            ->limit($limit)
            ->get();

        $this->info("Dispatching embedding jobs for {$posts->count()} posts...");

        foreach ($posts as $post) {
            GeneratePostEmbeddingJob::dispatch($post)->onQueue('embeddings');
        }

        $this->info('Done. Run: php artisan queue:work --queue=embeddings');
    }
}
*/
