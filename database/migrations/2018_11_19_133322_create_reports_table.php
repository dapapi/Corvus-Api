<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('weekly_newspaper', function (Blueprint $table) {
            $table->increments('id');
            $table->string('template_name',30)->comment('模块名');
            $table->string('colour',30)->nullable()->comment('颜色');
            $table->unsignedInteger('frequency')->nullable()->comment('频率');  //频率
            $table->string('department_id')->nullable()->comment('部门');  //部门
            $table->unsignedInteger('member');
            $table->unsignedInteger('deleted_at');
            $table->unsignedInteger('creator_id');
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
        Schema::dropIfExists('weekly_newspaper');
    }
}
