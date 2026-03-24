<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		if (Schema::hasTable('api_messages')) {
			return;
		}

		Schema::create('api_messages', function (Blueprint $table) {
			$table->id();
			$table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
			$table->string('provider')->nullable();
			$table->string('model')->nullable();
			$table->string('role')->default('user');
			$table->longText('content');
			$table->json('meta')->nullable();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('api_messages');
	}
};

