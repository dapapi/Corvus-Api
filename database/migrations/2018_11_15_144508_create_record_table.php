<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRecordTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('record', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('unit_name')->comment('单位名称');
            $table->string('department')->comment('部门');
            $table->string('position')->comment('职务');
            $table->dateTime('entry_time')->comment('入职时间');
            $table->dateTime('departure_time')->comment('离职时间');
            $table->string('monthly_pay')->comment('月薪');
            $table->string('departure_why')->comment('离职原因');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('record');
    }
}
