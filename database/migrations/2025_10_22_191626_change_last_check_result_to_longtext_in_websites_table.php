<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeLastCheckResultToLongtextInWebsitesTable extends Migration
{
    public function up()
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->longText('last_check_result')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->text('last_check_result')->nullable()->change();
        });
    }
}
