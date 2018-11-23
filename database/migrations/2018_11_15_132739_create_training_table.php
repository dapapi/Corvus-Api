<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrainingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('training', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('course_name')->nullable($value = true)->comment('培训课程名称');
            $table->string('certificate')->nullable($value = true)->comment('培训机构名称');
            $table->string('address')->nullable($value = true)->comment('地址');
            $table->dateTime('trained_time')->nullable($value = true)->comment('受训时间');
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
        Schema::dropIfExists('training');
    }
}
