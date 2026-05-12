<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nitik_errors', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('hash')->unique();
            $blueprint->string('level');
            $blueprint->string('exception_class')->nullable();
            $blueprint->text('message');
            $blueprint->string('file')->nullable();
            $blueprint->integer('line')->nullable();
            $blueprint->longText('stack_trace')->nullable();
            $blueprint->unsignedBigInteger('count')->default(1);
            $blueprint->timestamp('first_seen_at');
            $blueprint->timestamp('last_seen_at');
            $blueprint->boolean('is_resolved')->default(false);
            $blueprint->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nitik_errors');
    }
};
