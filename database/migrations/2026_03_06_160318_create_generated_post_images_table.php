<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_post_images', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Null until the user confirms — then set to the post ID
            $table->foreignId('post_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->text('prompt');
            $table->string('secure_url');
            $table->string('public_id')->nullable();

            // pending | confirmed | discarded
            $table->enum('status', ['pending', 'confirmed', 'discarded'])
                ->default('pending');

            $table->timestamps();

            // For cleanup command: find old pending images per user
            $table->index(['user_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_post_images');
    }
};
