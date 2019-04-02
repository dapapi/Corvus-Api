<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProjectImplodeTalentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_implode_talents', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('project_id')->comment('项目id');
            $table->unsignedInteger('team_m')->comment('目标艺人所在M组'); # 一对多问题
            $table->unsignedInteger('team_producer')->comment('目标博主所在制作组'); # 一对多问题
            $table->unsignedInteger('talent_id')->comment('目标艺人'); # 一对多问题
            $table->string('producer', 10)->comment('制作人'); # 一对多问题
            $table->unsignedInteger('producer_id')->comment('制作人id'); # 一对多问题
            $table->string('broker', 10)->comment('经纪人'); # 一对多问题
            $table->unsignedInteger('broker_id')->comment('经纪人id'); # 一对多问题
            $table->string('talent_type', 10)->comment('区分艺人还是博主');
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
        Schema::dropIfExists('project_implode_talents');
    }
}
