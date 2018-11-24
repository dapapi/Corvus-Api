<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('weekly_newspaper', function (Blueprint $table) {
            $table->increments('id');
            $table->string('template_name');
            $table->string('colour')->nullable();
            $table->unsignedInteger('frequency')->nullable();  //频率
            $table->string('department')->nullable();  //部门
            $table->unsignedInteger('issues_id');
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
        Schema::dropIfExists('weekly_newspaper');
    }
}
