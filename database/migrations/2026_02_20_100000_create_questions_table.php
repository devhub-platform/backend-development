<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('accepted_answer_id')->nullable();
            $table->string('title');
            $table->text('content');
            $table->string('slug')->unique();
            $table->boolean('is_resolved')->default(false);
            $table->integer('views')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('post_id');
            $table->index('is_resolved');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};

