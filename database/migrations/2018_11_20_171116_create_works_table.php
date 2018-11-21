<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWorksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('works', function (Blueprint $table) {
            $table->increments('id')->comment('作品ID');
            $table->unsignedInteger('star_id')->comment('明星ID');
            $table->string('name',50)->comment('作品名');
            $table->string('director',50)->comment('导演名');
            $table->date('release_time')->comment('发布时间');
            $table->integer('works_type')->comment('作品类型');
            $table->string('role',50)->comment('角色');
            $table->string('co-star',100)->comment('合作明星');
            $table->unsignedInteger('creator_id')->comment('创建者ID');
            $table->timestamps();

            $table->foreign('star_id')
                  ->references('id')
                  ->on('stars');

            $table->foreign('creator_id')
                  ->references('id')
                  ->on('users');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('works');
    }
}
