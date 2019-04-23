<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProjectTalentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_talent', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('project_id')->comment('项目id');
            $table->unsignedInteger('talent_id')->comment('艺人id');
            $table->string('talent_type')->comment('艺人类型');
            $table->string('talent_name')->comment('艺人名称');
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
        Schema::dropIfExists('project_talent');
    }
}
