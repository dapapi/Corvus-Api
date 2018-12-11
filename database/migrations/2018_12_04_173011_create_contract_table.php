<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContractTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         //项目合同
        Schema::create('contract', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('contract_no');  //合同编号
            $table->dateTime('sign_bill_time');    //签单时间
            $table->unsignedInteger('type_id');   //合同类型
            $table->string('contract_company',100);  //合同公司
            $table->string('creator_name',20); //创建人
            $table->unsignedInteger('partner_id');   // 1 艺人  2 工作室
            $table->unsignedInteger('star_id');   //艺人id
            $table->unsignedInteger('studio_id');   //工作室id
            $table->string('contract_name',100);  //合同公司
            $table->unsignedInteger('examine_status'); //审批状态
            $table->text('treaty_particulars');  //合同摘要
            $table->dateTime('contract_start_date');    //合约起始日
            $table->dateTime('contract_end_date');    //合约终止日
            $table->text('earnings');  //收益分配比例
            $table->unsignedInteger('certificate_id');//证件类别
            $table->unsignedInteger('certificate_number');//证件号码
            $table->unsignedInteger('certificate_affix_id');//证件id
            $table->unsignedInteger('scanning_affix_id');//扫描件id
            $table->unsignedInteger('scanning');//份数
            $table->unsignedInteger('contract_affix_id');//附件id
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
        Schema::dropIfExists('contract');
    }
}
