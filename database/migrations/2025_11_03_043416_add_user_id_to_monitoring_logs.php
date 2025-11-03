<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('monitoring_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('website_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('monitoring_logs', function (Blueprint $table) {
            $table->dropForeignKey(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
