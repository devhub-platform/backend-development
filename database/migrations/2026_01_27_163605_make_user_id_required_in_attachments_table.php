<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('attachments', function (Blueprint $table) {
            \App\Models\Attachment::whereNull('user_id')->delete();
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }

    public function down()
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
    }
};
