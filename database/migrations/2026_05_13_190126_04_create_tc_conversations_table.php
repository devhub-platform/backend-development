<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tc_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable()->index();
            $table->boolean('is_group')->default(false);
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tc_conversations');
    }
};
