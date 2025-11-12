<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop ALL possible unique indexes on 'url' column
        DB::statement('ALTER TABLE websites DROP INDEX IF EXISTS url');
        DB::statement('ALTER TABLE websites DROP INDEX IF EXISTS websites_url_unique');
        
        // Add composite unique
        Schema::table('websites', function (Blueprint $table) {
            $table->unique(['user_id', 'url'], 'websites_user_url_unique');
        });
    }

    public function down(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->dropUnique('websites_user_url_unique');
            $table->unique('url');
        });
    }
};
