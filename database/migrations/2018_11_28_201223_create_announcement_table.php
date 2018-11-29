<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAnnouncementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('announcement', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->tinyInteger('announcement');
            $table->tinyInteger('classify');
            $table->string('desc')->nullable();
            $table->tinyInteger('readflag')->default(0);
            $table->tinyInteger('is_accessory')->default(0);
            $table->string('accessory')->nullable();
            $table->tinyInteger('stick')->default(1);
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
        Schema::dropIfExists('announcement');
    }
}
