<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_scans', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->string('ip_address', 45);
            $table->string('user_agent', 500)->nullable();
            $table->string('status', 20)->default('pending');
            $table->boolean('has_suspicious_content')->default(false);
            $table->integer('suspicious_posts_count')->default(0);
            $table->json('scan_result')->nullable();
            $table->timestamps();
            
            $table->index('url');
            $table->index('ip_address');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_scans');
    }
};
