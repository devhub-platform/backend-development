<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tc_channels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable()->index();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('topic')->nullable();
            $table->string('type')->default('public');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tc_channels');
    }
};
