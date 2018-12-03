<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserWechatInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_wechat_infos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('union_id')->nullable();
            $table->string('nickname');
            $table->string('avatar');
            $table->string('province')->nullable();
            $table->string('language')->nullable();
            $table->string('privilege')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->integer('gender')->default(0);
            $table->unsignedInteger('user_id')->unique()->nullable();
            $table->foreign('user_id')->references('id')->on('users');
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
        Schema::dropIfExists('user_wechat_infos');
    }
}
