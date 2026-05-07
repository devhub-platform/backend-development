<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('log_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->foreignId('post_id')->nullable()->constrained()->onDelete('set null');

            $table->string('interaction_type');

            $table->integer('weight')->default(1);

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'topic_id']);
            $table->index(['created_at']);
            $table->index(['interaction_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_interactions');
    }
};
