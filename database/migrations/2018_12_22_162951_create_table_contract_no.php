<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableContractNo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contract_no', function (Blueprint $table) {
            $table->string("signing_subject")->comment("签约主体");
            $table->string("address_code")->comment("地址的拼音");
            $table->string("type")->comment("类别 SR ZC W");
            $table->integer("year")->comment("签约年份");
            $table->integer("no")->comment("编号目前最大值");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contract_no', function (Blueprint $table) {
            //
        });
    }
}
