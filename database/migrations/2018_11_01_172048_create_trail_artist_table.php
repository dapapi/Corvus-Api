<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrailArtistTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trail_artist', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('trail_id');
            $table->unsignedInteger('artist_id');
            $table->timestamps();

            $table->foreign('trail_id')->references('id')->on('trails');
            $table->foreign('artist_id')->references('id')->on('artists');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trail_artist');
    }
}
