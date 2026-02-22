<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->string('mime_type')->nullable()->after('filename');
            $table->unsignedBigInteger('size')->nullable()->after('mime_type');
            $table->string('type')->default('document')->after('size');
            $table->string('status')->default('pending')->after('type');
            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropIndex(['session_id']);
            $table->dropColumn(['mime_type', 'size', 'type', 'status']);
        });
    }
};
