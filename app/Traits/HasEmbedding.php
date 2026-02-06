<?php

namespace App\Traits;

use App\Models\ContentEmbedding;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasEmbedding
{
    public function embedding(): MorphOne
    {
        return $this->morphOne(ContentEmbedding::class, 'embeddable');
    }

    /**
     * Get the content that should be embedded for semantic search
     */
    abstract public function getEmbeddableContent(): string;

    /**
     * Get the content hash for change detection
     */
    public function getContentHash(): string
    {
        return md5($this->getEmbeddableContent());
    }

    /**
     * Check if embedding needs to be updated
     */
    public function needsEmbeddingUpdate(): bool
    {
        $embedding = $this->embedding;

        if (!$embedding) {
            return true;
        }

        return $embedding->content_hash !== $this->getContentHash();
    }
}

