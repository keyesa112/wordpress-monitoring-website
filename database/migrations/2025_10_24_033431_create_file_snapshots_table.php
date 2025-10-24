<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('file_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->onDelete('cascade');
            $table->string('file_path', 500);
            $table->string('file_hash', 64); // SHA256
            $table->bigInteger('file_size');
            $table->timestamp('last_modified');
            $table->string('status')->default('active'); // active, deleted
            $table->timestamps();
            
            $table->index(['website_id', 'file_path']);
            $table->index(['website_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('file_snapshots');
    }
};
