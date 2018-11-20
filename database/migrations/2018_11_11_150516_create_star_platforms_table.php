<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStarPlatformsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('star_platforms', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('star_id')->nullable();
            $table->unsignedInteger('platformable_id');
            $table->string('platformable_type');
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            $table->unsignedInteger('platform_id');
            $table->foreign('platform_id')
                ->references('id')
                ->on('platforms');

            $table->foreign('star_id')
                ->references('id')
                ->on('stars');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('star_platforms');
    }
}
