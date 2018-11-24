<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStarXiaohongshuInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('star_xiaohongshu_infos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('open_id')->comment('网站访问的openid');
            $table->string('url')->nullable()->comment('能拿到具体数据用的地址');
            $table->string('nickname')->comment('昵称');
            $table->string('avatar')->comment('头像');
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
        Schema::dropIfExists('user_xiaohongshu_infos');
    }
}
