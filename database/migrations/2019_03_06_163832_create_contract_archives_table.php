<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContractArchivesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contract_archives', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('contract_id')->comment('合同表对应id');
            $table->string('contract_number', 80)->comment('合同编号');
            $table->string('form_instance_number')->comment('合同审批编号');
            $table->string('archive')->comment('文件地址');
            $table->string('file_name')->comment('原文件名');
            $table->unsignedInteger('size')->comment('文件大小');
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
        Schema::dropIfExists('contract_archives');
    }
}
