<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
//            $table->string('education')->nullable()->after('email');
//            $table->string('work_at')->nullable()->after('education');
//            $table->string('location')->nullable()->after('work_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
//            $table->dropColumn('education');
//            $table->dropColumn('work_at');
//            $table->dropColumn('location');
        });
    }
};
