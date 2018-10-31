<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trails', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->unsignedInteger('principal_id')->nullable();
            $table->unsignedInteger('client_id')->nullable();
            $table->unsignedInteger('artist_id')->nullable();
            $table->unsignedInteger('contact_id')->nullable();
            $table->tinyInteger('type')->default(1);
            $table->tinyInteger('status')->default(1);
            $table->text('desc')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('principal_id')->references('id')->on('users');
            $table->foreign('client_id')->references('id')->on('users');
            $table->foreign('artist_id')->references('id')->on('artists');
            $table->foreign('contact_id')->references('id')->on('contacts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trails');
    }
}
