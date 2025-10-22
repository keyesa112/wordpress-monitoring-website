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
        Schema::create('websites', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nama website / klien
            $table->string('url')->unique(); // URL website
            $table->enum('status', ['online', 'offline', 'checking', 'error'])->default('checking');
            $table->integer('response_time')->nullable(); // dalam ms
            $table->integer('http_code')->nullable(); // HTTP status code
            $table->boolean('has_suspicious_content')->default(false); // Apakah ada konten mencurigakan
            $table->integer('suspicious_posts_count')->default(0); // Jumlah post mencurigakan
            $table->text('last_check_result')->nullable(); // JSON hasil cek terakhir
            $table->timestamp('last_checked_at')->nullable(); // Waktu cek terakhir
            $table->boolean('is_active')->default(true); // Aktif monitoring atau tidak
            $table->text('notes')->nullable(); // Catatan admin
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('websites');
    }
};