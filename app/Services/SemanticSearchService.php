<?php

namespace App\Services;

use App\Models\ContentEmbedding;
use App\Models\Post;
use App\Models\User;
use App\Models\Tag;
use App\Models\Comment;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SemanticSearchService
{
    protected Client $client;
    protected string $embeddingModel;

    private const CACHE_TTL = 3600; // 1 hour
    private const DEFAULT_LIMIT = 10;
    private const SIMILARITY_THRESHOLD = 0.5;


    public const EMBEDDING_MODELS = [
        'qwen/qwen3-embedding-8b' => [
            'name' => 'Qwen3 Embedding 8B',
            'dimension' => 4096,
            'best_for' => 'Multilingual, long-text understanding',
        ],
        'mistralai/codestral-embed-2505' => [
            'name' => 'Codestral Embed',
            'dimension' => 1024,
            'best_for' => 'Code embedding, repositories',
        ],
        'openai/text-embedding-3-small' => [
            'name' => 'Text Embedding 3 Small',
            'dimension' => 1536,
            'best_for' => 'General purpose, cost-effective',
        ],
        'openai/text-embedding-3-large' => [
            'name' => 'Text Embedding 3 Large',
            'dimension' => 3072,
            'best_for' => 'High accuracy, multilingual',
        ],
        'thenlper/gte-large' => [
            'name' => 'GTE-Large',
            'dimension' => 1024,
            'best_for' => 'English text, semantic similarity',
        ],
        'intfloat/multilingual-e5-large' => [
            'name' => 'Multilingual E5 Large',
            'dimension' => 1024,
            'best_for' => 'Multilingual (90+ languages)',
        ],
    ];

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('services.openrouter.base_url', 'https://openrouter.ai/api/v1/'),
            'headers' => [
                'Authorization' => 'Bearer ' . config('services.openrouter.api_key'),
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url'),
                'X-Title' => config('app.name'),
            ],
            'timeout' => 30,
        ]);

        $this->embeddingModel = config('services.openrouter.embedding_model', 'openai/text-embedding-3-small');
    }

    /**
     * Get available embedding models
     */
    public function getAvailableModels(): array
    {
        return self::EMBEDDING_MODELS;
    }

    /**
     * Set the embedding model to use
     */
    public function setModel(string $model): self
    {
        if (!array_key_exists($model, self::EMBEDDING_MODELS)) {
            throw new \InvalidArgumentException("Unknown embedding model: {$model}");
        }

        $this->embeddingModel = $model;
        return $this;
    }

    /**
     * Perform semantic search across all content types
     */
    public function search(string $query, array $options = []): array
    {
        $types = $options['types'] ?? ['posts', 'users', 'tags', 'comments'];
        $limit = $options['limit'] ?? self::DEFAULT_LIMIT;
        $threshold = $options['threshold'] ?? self::SIMILARITY_THRESHOLD;
        $model = $options['model'] ?? $this->embeddingModel;

        // Use specified model for this search
        if ($model !== $this->embeddingModel) {
            $this->setModel($model);
        }

        // Generate embedding for the search query
        $queryEmbedding = $this->generateEmbedding($query);

        if (empty($queryEmbedding)) {
            Log::warning('Failed to generate embedding for query', ['query' => $query]);
            return $this->fallbackSearch($query, $types, $limit);
        }

        $results = [];

        if (in_array('posts', $types)) {
            $results['posts'] = $this->searchByType(Post::class, $queryEmbedding, $limit, $threshold);
        }

        if (in_array('users', $types)) {
            $results['users'] = $this->searchByType(User::class, $queryEmbedding, $limit, $threshold);
        }

        if (in_array('tags', $types)) {
            $results['tags'] = $this->searchByType(Tag::class, $queryEmbedding, $limit, $threshold);
        }

        if (in_array('comments', $types)) {
            $results['comments'] = $this->searchByType(Comment::class, $queryEmbedding, $limit, $threshold);
        }

        return $results;
    }

    /**
     * Search within a specific content type using semantic similarity
     */
    protected function searchByType(string $modelClass, array $queryEmbedding, int $limit, float $threshold): Collection
    {
        $embeddings = ContentEmbedding::where('embeddable_type', $modelClass)
            ->get();

        $results = [];

        foreach ($embeddings as $embedding) {
            $similarity = $this->cosineSimilarity($queryEmbedding, $embedding->embedding);

            if ($similarity >= $threshold) {
                $results[] = [
                    'embedding' => $embedding,
                    'similarity' => $similarity,
                ];
            }
        }

        // Sort by similarity (descending)
        usort($results, fn($a, $b) => $b['similarity'] <=> $a['similarity']);

        // Take top results and load the actual models
        $topResults = array_slice($results, 0, $limit);

        $modelIds = array_map(fn($r) => $r['embedding']->embeddable_id, $topResults);

        $models = $modelClass::whereIn('id', $modelIds)->get();

        // Add similarity score to each model
        return $models->map(function ($model) use ($topResults) {
            foreach ($topResults as $result) {
                if ($result['embedding']->embeddable_id === $model->id) {
                    $model->similarity_score = round($result['similarity'] * 100, 2);
                    break;
                }
            }
            return $model;
        })->sortByDesc('similarity_score')->values();
    }

    /**
     * Generate embedding for text using OpenRouter API
     */
    public function generateEmbedding(string $text, ?string $model = null): array
    {
        $model = $model ?? $this->embeddingModel;
        $cacheKey = 'embedding_' . md5($text . $model);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($text, $model) {
            try {
                // Truncate text if too long (max ~8000 tokens)
                $text = $this->truncateText($text, 8000);

                Log::info('Generating embedding', [
                    'model' => $model,
                    'text_length' => strlen($text),
                ]);

                $response = $this->client->post('embeddings', [
                    'json' => [
                        'model' => $model,
                        'input' => $text,
                    ],
                ]);

                $data = json_decode($response->getBody(), true);

                if (isset($data['data'][0]['embedding'])) {
                    Log::info('Embedding generated successfully', [
                        'model' => $model,
                        'dimension' => count($data['data'][0]['embedding']),
                    ]);
                    return $data['data'][0]['embedding'];
                }

                Log::warning('Unexpected embedding response format', ['data' => $data]);
                return [];

            } catch (GuzzleException $e) {
                Log::error('Embedding generation failed', [
                    'error' => $e->getMessage(),
                    'model' => $model,
                    'text_length' => strlen($text),
                ]);
                return [];
            }
        });
    }

    /**
     * Store or update embedding for a model
     */
    public function storeEmbedding($model, ?string $embeddingModel = null): ?ContentEmbedding
    {
        if (!method_exists($model, 'getEmbeddableContent')) {
            Log::warning('Model does not have getEmbeddableContent method', [
                'model' => get_class($model),
            ]);
            return null;
        }

        $embeddingModel = $embeddingModel ?? $this->embeddingModel;
        $content = $model->getEmbeddableContent();
        $contentHash = md5($content);

        // Check if embedding already exists and is up to date
        $existing = ContentEmbedding::where('embeddable_type', get_class($model))
            ->where('embeddable_id', $model->id)
            ->first();

        if ($existing && $existing->content_hash === $contentHash && $existing->model_used === $embeddingModel) {
            return $existing;
        }

        $embedding = $this->generateEmbedding($content, $embeddingModel);

        if (empty($embedding)) {
            return null;
        }

        return ContentEmbedding::updateOrCreate(
            [
                'embeddable_type' => get_class($model),
                'embeddable_id' => $model->id,
            ],
            [
                'content_hash' => $contentHash,
                'embedding' => $embedding,
                'model_used' => $embeddingModel,
            ]
        );
    }

    /**
     * Batch generate embeddings for multiple models
     */
    public function batchStoreEmbeddings(Collection $models, ?string $embeddingModel = null, callable $progressCallback = null): array
    {
        $stats = ['success' => 0, 'failed' => 0, 'skipped' => 0];
        $embeddingModel = $embeddingModel ?? $this->embeddingModel;

        foreach ($models as $index => $model) {
            if (!method_exists($model, 'getEmbeddableContent')) {
                $stats['skipped']++;
                continue;
            }

            $contentHash = md5($model->getEmbeddableContent());

            // Check if already up to date
            $existing = ContentEmbedding::where('embeddable_type', get_class($model))
                ->where('embeddable_id', $model->id)
                ->where('content_hash', $contentHash)
                ->where('model_used', $embeddingModel)
                ->exists();

            if ($existing) {
                $stats['skipped']++;
                if ($progressCallback) {
                    $progressCallback($index + 1, $models->count(), 'skipped');
                }
                continue;
            }

            $result = $this->storeEmbedding($model, $embeddingModel);

            if ($result) {
                $stats['success']++;
            } else {
                $stats['failed']++;
            }

            if ($progressCallback) {
                $progressCallback($index + 1, $models->count(), $result ? 'success' : 'failed');
            }

            // Rate limiting - wait 100ms between requests
            usleep(100000);
        }

        return $stats;
    }

    /**
     * Calculate cosine similarity between two vectors
     */
    protected function cosineSimilarity(array $a, array $b): float
    {
        if (empty($a) || empty($b) || count($a) !== count($b)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($a as $i => $val) {
            $dotProduct += $val * $b[$i];
            $normA += $val * $val;
            $normB += $b[$i] * $b[$i];
        }

        $normA = sqrt($normA);
        $normB = sqrt($normB);

        if ($normA == 0 || $normB == 0) {
            return 0.0;
        }

        return $dotProduct / ($normA * $normB);
    }

    /**
     * Truncate text to approximate token limit
     */
    protected function truncateText(string $text, int $maxTokens): string
    {
        // Rough estimate: 1 token ≈ 4 characters for English text
        $maxChars = $maxTokens * 4;

        if (strlen($text) <= $maxChars) {
            return $text;
        }

        return substr($text, 0, $maxChars);
    }

    /**
     * Fallback to traditional search when semantic search fails
     */
    protected function fallbackSearch(string $query, array $types, int $limit): array
    {
        $results = [];

        if (in_array('posts', $types)) {
            $results['posts'] = Post::where('title', 'LIKE', "%{$query}%")
                ->orWhere('content', 'LIKE', "%{$query}%")
                ->limit($limit)
                ->get();
        }

        if (in_array('users', $types)) {
            $results['users'] = User::where('name', 'LIKE', "%{$query}%")
                ->orWhere('username', 'LIKE', "%{$query}%")
                ->orWhere('bio', 'LIKE', "%{$query}%")
                ->limit($limit)
                ->get();
        }

        if (in_array('tags', $types)) {
            $results['tags'] = Tag::where('name', 'LIKE', "%{$query}%")
                ->orWhere('description', 'LIKE', "%{$query}%")
                ->limit($limit)
                ->get();
        }

        if (in_array('comments', $types)) {
            $results['comments'] = Comment::where('content', 'LIKE', "%{$query}%")
                ->limit($limit)
                ->get();
        }

        return $results;
    }

    /**
     * Get search suggestions based on similar queries
     */
    public function getSuggestions(string $partialQuery, int $limit = 5): array
    {
        // Get recent search terms and find similar ones
        $recentSearches = Cache::get('recent_searches', []);

        if (empty($recentSearches)) {
            return [];
        }

        $queryEmbedding = $this->generateEmbedding($partialQuery);

        if (empty($queryEmbedding)) {
            return array_filter($recentSearches, fn($s) => str_contains(strtolower($s), strtolower($partialQuery)));
        }

        $suggestions = [];

        foreach ($recentSearches as $search => $embedding) {
            if (is_array($embedding)) {
                $similarity = $this->cosineSimilarity($queryEmbedding, $embedding);
                $suggestions[$search] = $similarity;
            }
        }

        arsort($suggestions);

        return array_slice(array_keys($suggestions), 0, $limit);
    }

    public function findSimilar($model, int $limit = 5, array $with = []): Collection
    {
        $embedding = ContentEmbedding::where('embeddable_type', get_class($model))
            ->where('embeddable_id', $model->id)
            ->first();

        if (!$embedding) {
            return collect([]);
        }

        $results = $this->searchByType(
            get_class($model),
            $embedding->embedding,
            $limit + 1, // +1 to exclude self
            self::SIMILARITY_THRESHOLD
        );

        // Filter out the original model and take the limit
        $filtered = $results->filter(fn($m) => $m->id !== $model->id)->take($limit);

        // If relations need to be loaded, fetch fresh models with relations
        if (!empty($with) && $filtered->isNotEmpty()) {
            $modelClass = get_class($model);
            $ids = $filtered->pluck('id')->toArray();
            $scores = $filtered->pluck('similarity_score', 'id')->toArray();

            $freshModels = $modelClass::whereIn('id', $ids)->with($with)->get();

            // Re-attach similarity scores
            return $freshModels->map(function ($m) use ($scores) {
                $m->similarity_score = $scores[$m->id] ?? null;
                return $m;
            })->sortByDesc('similarity_score')->values();
        }

        return $filtered->values();
    }

    /**
     * Get embedding statistics
     */
    public function getStats(): array
    {
        return [
            'total_embeddings' => ContentEmbedding::count(),
            'by_type' => ContentEmbedding::select('embeddable_type', DB::raw('count(*) as count'))
                ->groupBy('embeddable_type')
                ->pluck('count', 'embeddable_type')
                ->toArray(),
            'by_model' => ContentEmbedding::select('model_used', DB::raw('count(*) as count'))
                ->groupBy('model_used')
                ->pluck('count', 'model_used')
                ->toArray(),
            'current_model' => $this->embeddingModel,
            'available_models' => self::EMBEDDING_MODELS,
        ];
    }
}

