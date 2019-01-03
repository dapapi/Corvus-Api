<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusCloumAttendances extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // todo 处理这个字段
        Schema::table('attendances', function (Blueprint $table) {
            $table->integer('status')->comment("状态");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {
            //
        });
    }
}
