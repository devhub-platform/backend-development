<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('question_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('vote_type', ['upvote', 'downvote']);
            $table->timestamps();

            $table->unique(['question_id', 'user_id']);
            $table->index(['question_id', 'vote_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_votes');
    }
};
