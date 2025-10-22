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
        Schema::create('monitoring_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->onDelete('cascade');
            $table->enum('check_type', ['ping', 'backlink', 'full']); // Tipe pengecekan
            $table->enum('status', ['online', 'offline', 'error'])->nullable();
            $table->integer('response_time')->nullable(); // dalam ms
            $table->integer('http_code')->nullable();
            $table->boolean('has_suspicious_content')->default(false);
            $table->integer('suspicious_posts_count')->default(0);
            $table->json('suspicious_posts')->nullable(); // Detail post mencurigakan
            $table->text('error_message')->nullable(); // Pesan error jika ada
            $table->json('raw_result')->nullable(); // Hasil mentah untuk analisis
            $table->timestamps();
            
            // Index untuk performa query
            $table->index(['website_id', 'created_at']);
            $table->index('check_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_logs');
    }
};