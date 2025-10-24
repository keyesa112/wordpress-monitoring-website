<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('file_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->onDelete('cascade');
            $table->string('file_path', 500);
            $table->string('change_type'); // new, modified, deleted
            $table->string('old_hash', 64)->nullable();
            $table->string('new_hash', 64)->nullable();
            $table->boolean('is_suspicious')->default(false);
            $table->json('suspicious_patterns')->nullable(); // keyword yang ditemukan
            $table->text('file_preview')->nullable(); // 500 karakter pertama
            $table->string('severity')->default('info'); // info, warning, critical
            $table->text('recommendation')->nullable();
            $table->timestamps();
            
            $table->index(['website_id', 'change_type']);
            $table->index(['website_id', 'is_suspicious']);
            $table->index(['website_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('file_changes');
    }
};
