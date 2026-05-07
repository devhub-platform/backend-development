<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("UPDATE posts SET image_url = NULL WHERE image_url IS NULL OR image_url = ''");
            DB::statement("UPDATE posts SET image_url = JSON_ARRAY(image_url) WHERE image_url IS NOT NULL AND JSON_VALID(image_url) = 0");
            DB::statement('ALTER TABLE posts MODIFY image_url JSON NULL');
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE posts MODIFY image_url VARCHAR(255) NULL');
        }
    }
};

