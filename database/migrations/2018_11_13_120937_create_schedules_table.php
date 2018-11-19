<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->unsignedInteger('calendar_id');
            $table->boolean('is_allday')->default(0);
            $table->boolean('privacy')->default(0);
            $table->dateTime('star_at');
            $table->dateTime('end_at');
            $table->string('position')->nullable();
            $table->tinyInteger('repeat')->default(0);
            $table->unsignedInteger('material_id')->nullable();
            $table->unsignedInteger('creator_id');
            $table->tinyInteger('type')->default(1);
            $table->tinyInteger('status')->default(1);
            $table->string('desc');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('calendar_id')->references('id')->on('calendars');
            $table->foreign('creator_id')->references('id')->on('users');
            $table->foreign('material_id')->references('id')->on('materials');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('schedules');
    }
}
