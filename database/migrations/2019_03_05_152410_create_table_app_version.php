<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableAppVersion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_version', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('pt')->comment('1:安卓 2:ios');
            $table->float("version_code")->comment("版本号(内部)");
            $table->string("update_log")->nullable()->comment('版本描述');
            $table->integer("update_install")->comment("是否强制更新 1:强制 2:否");
            $table->integer("download_url")->comment("下载地址");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('app_version', function (Blueprint $table) {
            //
        });
    }
}
