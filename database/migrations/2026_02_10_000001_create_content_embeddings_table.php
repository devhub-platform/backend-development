<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('content_embeddings', function (Blueprint $table) {
            $table->id();
            $table->morphs('embeddable');
            $table->string('content_hash', 32); // MD5 hash is always 32 characters
            $table->longText('embedding'); // JSON array of floats can be large
            $table->string('model_used')->default('text-embedding-3-small');
            $table->timestamps();

            $table->unique(['embeddable_type', 'embeddable_id']);
            $table->index('content_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_embeddings');
    }
};

