<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\User;
use App\Models\Tag;
use App\Models\Comment;
use App\Services\SemanticSearchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateEmbeddings extends Command
{
    protected $signature = 'embeddings:generate
                            {--type=all : Type of content to embed (all, posts, users, tags, comments)}
                            {--model= : Embedding model to use (leave empty for default)}
                            {--force : Force regenerate all embeddings even if they exist}
                            {--limit= : Limit the number of items to process}';

    protected $description = 'Generate embeddings for content to enable semantic search';

    protected SemanticSearchService $semanticSearch;

    public function __construct(SemanticSearchService $semanticSearch)
    {
        parent::__construct();
        $this->semanticSearch = $semanticSearch;
    }

    public function handle(): int
    {
        $type = $this->option('type');
        $force = $this->option('force');
        $limit = $this->option('limit');
        $model = $this->option('model');

        // Validate and set model
        if ($model) {
            $availableModels = array_keys($this->semanticSearch->getAvailableModels());
            if (!in_array($model, $availableModels)) {
                $this->error("Invalid model: {$model}");
                $this->info("Available models:");
                foreach ($this->semanticSearch->getAvailableModels() as $id => $info) {
                    $this->line("  - {$id} ({$info['name']}) - Best for: {$info['best_for']}");
                }
                return Command::FAILURE;
            }
            $this->semanticSearch->setModel($model);
        }

        $this->info(' Starting embedding generation...');
        $this->info(' Using model: ' . ($model ?? config('services.openrouter.embedding_model', 'openai/text-embedding-3-small')));
        $this->newLine();

        $totalStats = ['success' => 0, 'failed' => 0, 'skipped' => 0];

        $types = $type === 'all'
            ? ['posts', 'users', 'tags', 'comments']
            : [$type];

        foreach ($types as $contentType) {
            $stats = $this->processType($contentType, $force, $limit, $model);
            $totalStats['success'] += $stats['success'];
            $totalStats['failed'] += $stats['failed'];
            $totalStats['skipped'] += $stats['skipped'];
        }

        $this->newLine();
        $this->info(' Embedding generation complete!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Successful', $totalStats['success']],
                ['Failed', $totalStats['failed']],
                ['Skipped (up to date)', $totalStats['skipped']],
            ]
        );

        return Command::SUCCESS;
    }

    protected function processType(string $type, bool $force, ?int $limit, ?string $model): array
    {
        $modelClass = match ($type) {
            'posts' => Post::class,
            'users' => User::class,
            'tags' => Tag::class,
            'comments' => Comment::class,
            default => null,
        };

        if (!$modelClass) {
            $this->error("Unknown type: {$type}");
            return ['success' => 0, 'failed' => 0, 'skipped' => 0];
        }

        $query = $modelClass::query();

        // For posts, only embed published ones
        if ($type === 'posts') {
            $query->where('status', 'published');
        }

        if ($limit) {
            $query->limit($limit);
        }

        $items = $query->get();
        $total = $items->count();

        if ($total === 0) {
            $this->warn("No {$type} found to process.");
            return ['success' => 0, 'failed' => 0, 'skipped' => 0];
        }

        $this->info(" Processing {$total} {$type}...");
        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        $stats = ['success' => 0, 'failed' => 0, 'skipped' => 0];

        foreach ($items as $item) {
            try {
                if (!$force && $this->hasValidEmbedding($item, $model)) {
                    $stats['skipped']++;
                    $progressBar->advance();
                    continue;
                }

                $result = $this->semanticSearch->storeEmbedding($item, $model);

                if ($result) {
                    $stats['success']++;
                } else {
                    $stats['failed']++;
                }

            } catch (\Exception $e) {
                Log::error("Embedding generation failed for {$type} #{$item->id}", [
                    'error' => $e->getMessage(),
                ]);
                $stats['failed']++;
            }

            $progressBar->advance();

            usleep(150000); // 150ms delay
        }

        $progressBar->finish();
        $this->newLine();

        $this->info("  ✓ {$stats['success']} successful, {$stats['failed']} failed, {$stats['skipped']} skipped");

        return $stats;
    }

    protected function hasValidEmbedding($model, ?string $embeddingModel): bool
    {
        if (!method_exists($model, 'getEmbeddableContent')) {
            return false;
        }

        $embeddingModel = $embeddingModel ?? config('services.openrouter.embedding_model', 'openai/text-embedding-3-small');

        $embedding = \App\Models\ContentEmbedding::where('embeddable_type', get_class($model))
            ->where('embeddable_id', $model->id)
            ->first();

        if (!$embedding) {
            return false;
        }

        return $embedding->content_hash === md5($model->getEmbeddableContent())
            && $embedding->model_used === $embeddingModel;
    }
}

