<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('answer_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('answer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('vote_type', ['upvote', 'downvote']);
            $table->timestamps();

            // One vote per user per answer
            $table->unique(['answer_id', 'user_id']);
            $table->index('answer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answer_votes');
    }
};
