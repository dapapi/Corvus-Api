<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrailStarTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trail_star', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('trail_id');
            $table->unsignedInteger('star_id');
            $table->tinyInteger('type');
            $table->timestamps();

            $table->foreign('trail_id')->references('id')->on('trails');
            $table->foreign('star_id')->references('id')->on('stars');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trail_star');
    }
}
