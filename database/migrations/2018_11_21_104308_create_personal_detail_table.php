<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePersonalDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('personal_detail', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_card_url')->nullable($value = true)->comment('身份证url');
            $table->string('passport_code')->nullable($value = true)->comment('护照号');
            $table->string('id_number')->nullable($value = true)->comment('身份证号');
            $table->string('card_number_one')->nullable($value = true)->comment('工资银行卡号1');
            $table->string('card_number_two')->nullable($value = true)->comment('工资银行卡号2');
            $table->string('credit_card')->nullable($value = true)->comment('信用卡');
            $table->string('accumulation_fund',50)->nullable($value = true)->comment('公积金');
            $table->string('opening',50)->nullable($value = true)->comment('开户行');
            $table->string('last_company',50)->nullable($value = true)->comment('上家公司');
            $table->string('responsibility')->nullable($value = true)->comment('岗位职责');
            $table->string('contract')->nullable($value = true)->comment('合同');
            $table->string('address')->nullable($value = true)->comment('地址');
            $table->dateTime('entry_time')->nullable($value = true)->comment('入职时间');
            $table->timestamps();
            $table->softDeletes();


            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('personal_detail');
    }
}
