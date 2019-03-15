<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDepartmentDashboardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('department_dashboards', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->comment('仪表盘名称');
            $table->string('includes')->comment('包含内容记录');
            $table->unsignedInteger('creator_id')->comment('创建人id');
            $table->unsignedInteger('department_id')->comment('关联部门id');
            $table->text('desc')->comment('仪表盘描述');
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
        Schema::dropIfExists('department_dashboards');
    }
}
