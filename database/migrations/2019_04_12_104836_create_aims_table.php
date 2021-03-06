<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAimsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('aims', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 50)->comment('目标名称');
            $table->unsignedTinyInteger('range')->comment('目标范围');
            $table->unsignedInteger('department_id')->nullable()->comment('对应部门');
            $table->string('department_name', 40)->nullable()->comment('部门名称');
            $table->unsignedInteger('period_id')->comment('对应周期');
            $table->string('period_name', 40)->comment('周期名称');
            $table->unsignedTinyInteger('type')->comment('目标类型');
            $table->unsignedTinyInteger('amount_type')->nullable()->comment('金额类型');
            $table->float('amount', 12, 2)->nullable()->comment('目标金额');
            $table->unsignedTinyInteger('position')->comment('维度');
            $table->unsignedTinyInteger('talent_level')->nullable()->comment('艺人级别');
            $table->unsignedTinyInteger('aim_level')->comment('目标级别');
            $table->unsignedInteger('principal_id')->comment('负责人');
            $table->string('principal_name', 20)->comment('负责人姓名');
            $table->unsignedInteger('creator_id')->comment('创建人id');
            $table->string('creator_name', 20)->comment('创建人姓名');
            $table->string('desc', 80)->nullable()->comment('目标描述');
            $table->float('percentage', 5, 2)->nullable()->comment('目标进度');
            $table->date('deadline')->nullable()->comment('截止日期');
            $table->unsignedTinyInteger('status')->default(0)->comment('目标状态');
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
        Schema::dropIfExists('aims');
    }
}
