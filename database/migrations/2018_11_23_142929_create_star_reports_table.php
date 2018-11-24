<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStarReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('star_reports', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('starable_id');
            $table->string('starable_type');
            $table->unsignedInteger('platform_id');
            $table->unsignedInteger('count')->default(0);

            $table->foreign('platform_id')
                ->references('id')
                ->on('platforms');
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
        Schema::dropIfExists('star_reports');
    }
}
