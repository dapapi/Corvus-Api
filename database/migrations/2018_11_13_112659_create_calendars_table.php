<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCalendarsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('calendars', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->unique();
            $table->string('color');
            $table->boolean('privacy')->default(0);
            $table->unsignedInteger('starable_id')->nullable();
            $table->string('starable_type')->nullable();
            $table->unsignedInteger('creator_id');
            $table->tinyInteger('type')->default(1);
            $table->tinyInteger('status')->default(1);
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('creator_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('calendars');
    }
}
