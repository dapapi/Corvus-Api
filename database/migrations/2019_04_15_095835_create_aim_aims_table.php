<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAimAimsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('aim_aims', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('p_aim_id')->comment('父级目标id');
            $table->string('p_aim_name', 40)->comment('父级目标名称');
            $table->unsignedTinyInteger('p_aim_range')->comment('父级目标范围');
            $table->unsignedInteger('c_aim_id')->comment('子级目标id');
            $table->string('c_aim_name', 40)->comment('子级目标名称');
            $table->unsignedTinyInteger('c_aim_range')->comment('子级目标范围');
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
        Schema::dropIfExists('aim_aims');
    }
}
