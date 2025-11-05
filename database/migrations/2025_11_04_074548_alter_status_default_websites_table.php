<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->string('status')->default('offline')->change();
        });
    }

    public function down()
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->string('status')->default(null)->change();
        });
    }

};
