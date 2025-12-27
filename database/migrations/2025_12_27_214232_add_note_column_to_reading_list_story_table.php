<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reading_list_story', function (Blueprint $table) {
           //
        });
    }

    public function down(): void
    {
        Schema::table('reading_list_story', function (Blueprint $table) {
            //
        });
    }
};
