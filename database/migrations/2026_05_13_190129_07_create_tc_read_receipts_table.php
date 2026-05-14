<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tc_read_receipts', function (Blueprint $table) {
            $table->id();
            $table->morphs('readable');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('last_read_message_id')->constrained('tc_messages')->cascadeOnDelete();
            $table->timestamp('read_at');

            $table->unique(['readable_type', 'readable_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tc_read_receipts');
    }
};
