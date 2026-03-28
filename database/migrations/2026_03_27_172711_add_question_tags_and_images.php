<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ─── question_tag (many-to-many) ──────────────────────────────────────
        Schema::create('question_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->unique(['question_id', 'tag_id']);
            $table->timestamps();
        });

        // ─── question_images ──────────────────────────────────────────────────
        Schema::create('question_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('url');
            $table->string('file_id')->nullable(); // HackClub file identifier
            $table->timestamps();

            $table->index('question_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_images');
        Schema::dropIfExists('question_tag');
    }
};
