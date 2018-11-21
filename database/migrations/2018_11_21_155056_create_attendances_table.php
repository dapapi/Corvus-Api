<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('type')->comment('申请类型 1:请假 2:加班 3:出差 4:外勤');
            $table->date('start_at')->comment("开始时间");
            $table->date('end_at')->comment('结束时间');
            $table->date('number')->comment('天数');
            $table->string('cause')->comment('事由');
            $table->string('affixes')->comment('附件');
            $table->integer('Approval_flow')->comment('审批人');
            $table->string('notification_person')->comment('知会人');
            $table->integer("creator_id")->comment("申请人");
            $table->integer("leave_type")->comment("请假类型 1:事假，2:病假，3:调休假，4:年假，5:婚假，6:产假，7:陪产假，8:丧假，9:其他");
            $table->string("place")->comment("出差或者外勤地点");
            $table->softDeletes();
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
        Schema::dropIfExists('attendances');
    }
}
