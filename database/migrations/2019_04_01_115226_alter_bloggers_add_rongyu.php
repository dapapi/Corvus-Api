<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterBloggersAddRongyu extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bloggers', function (Blueprint $table) {
            $table->string('last_updated_user')->comment('最近更新人,同步于operate_logs表')->nullable();
            $table->dateTime("last_updated_at")->comment('最近更新时间，同步于operate_logs表')->nullable();
            $table->dateTime("last_follow_up_at")->comment('最近跟进时间，同步于operate_logs表')->nullable();
//            $table->dateTime('contract_end_date')->comment('合同开始时间')->nullable();
//            $table->dateTime('contract_start_date')->comment('合同开始时间')->nullable();
//            $table->json("publicity")->comment("json数据，存储制作人")->nullable();
            $table->string('publicity_deparment_ids')->comment('存储制作人部门id列表')->nullable();
            $table->string('publicity_user_ids')->comment('存储制作人id列表')->nullable();
            $table->string('publicity_user_names')->comment('存储制作人列表')->nullable();
//            $table->json("publicity")->comment("json数据，存储制作人")->nullable();
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stars', function (Blueprint $table) {
            //
        });
    }
}
