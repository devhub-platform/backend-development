<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Intentionally left blank: CSV imports now map only to fields that already exist on the Post model.
    }

    public function down(): void
    {
        // Intentionally left blank.
    }
};


