<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks how many AI chat prompts each user has consumed.
 *
 * A single row per user is upserted on every prompt, making daily/monthly
 * resets cheap and the current count instantly readable without a SUM query.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_prompt_usage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedInteger('daily_count')->default(0);
            $table->unsignedInteger('monthly_count')->default(0);
            $table->date('last_daily_reset')->nullable();
            $table->date('last_monthly_reset')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_prompt_usage');
    }
};
