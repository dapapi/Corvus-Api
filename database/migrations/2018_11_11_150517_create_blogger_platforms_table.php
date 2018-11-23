<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBloggerPlatformsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('blogger_platforms', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('blogger_id')->nullable();
            $table->unsignedInteger('platformable_id');
            $table->string('platformable_type');
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            $table->unsignedInteger('platform_id');
            $table->foreign('platform_id')
                ->references('id')
                ->on('platforms');

            $table->foreign('blogger_id')
                ->references('id')
                ->on('bloggers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('blogger_platforms');
    }
}
