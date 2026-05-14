<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tc_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('tc_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('type'); // user, channel, here
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tc_mentions');
    }
};
