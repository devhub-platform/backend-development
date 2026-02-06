<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentEmbedding extends Model
{
    protected $fillable = [
        'embeddable_type',
        'embeddable_id',
        'content_hash',
        'embedding',
        'model_used',
    ];

    protected $casts = [
        'embedding' => 'array',
    ];

    public function embeddable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Calculate cosine similarity between this embedding and another vector
     */
    public function cosineSimilarity(array $otherVector): float
    {
        $embedding = $this->embedding;

        if (empty($embedding) || empty($otherVector)) {
            return 0.0;
        }

        if (count($embedding) !== count($otherVector)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($embedding as $i => $val) {
            $dotProduct += $val * $otherVector[$i];
            $normA += $val * $val;
            $normB += $otherVector[$i] * $otherVector[$i];
        }

        $normA = sqrt($normA);
        $normB = sqrt($normB);

        if ($normA == 0 || $normB == 0) {
            return 0.0;
        }

        return $dotProduct / ($normA * $normB);
    }
}

