<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePersonalSalaryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('personal_salary', function (Blueprint $table) {

            $table->increments('id');
            $table->dateTime('entry_time')->nullable($value = true)->comment('入职时间');
            $table->dateTime('trial_end_time')->nullable($value = true)->comment('试用期截止时间');
            $table->dateTime('pdeparture_time')->nullable($value = true)->comment('离职时间');
            $table->string('share_department',20)->nullable($value = true)->comment('分摊部门');
            $table->string('jobs',50)->nullable($value = true)->comment('岗位');
            $table->string('income_tax',20)->nullable($value = true)->comment('个税纳税方式');
            $table->string('personnel_category',20)->nullable($value = true)->comment('人员类别');
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('personal_salary');
    }
}
