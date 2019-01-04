<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('contract_number', 80)->comment('后端生成合同编号');
            $table->string('form_instance_number')->comment('对应合同审批编号');
            $table->unsignedInteger('creator_id')->comment('创建人id');
            $table->string('creator_name', 50)->comment('创建人name');
            $table->unsignedInteger('project_id')->comment('关联项目id');
            $table->tinyInteger('type')->comment('合同类型，取字典中相应id');
            $table->string('stars')->comment('记相关艺人ids，以|分割');
            $table->string('star_type')->comment('区分艺人还是博主');
            $table->unsignedInteger('updater_id')->comment('更新人id');
            $table->unsignedInteger('updater_name')->comment('更新人name');
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
        Schema::dropIfExists('contracts');
    }
}
