<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAimProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('aim_projects', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('aim_id')->comment('目标Id');
            $table->unsignedInteger('project_id')->comment('项目id');
            $table->string('project_name', 80)->comment('项目名称');
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
        Schema::dropIfExists('aim_projects');
    }
}
