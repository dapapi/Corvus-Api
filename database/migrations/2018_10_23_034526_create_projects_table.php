<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->unsignedInteger('principal_id');
            $table->unsignedInteger('creator_id');
            $table->unsignedInteger('company_id')->nullable();
            $table->unsignedInteger('trail_id')->nullable();
            $table->unsignedInteger('contact_id')->nullable();
            $table->boolean('privacy')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('type')->default(1);
            $table->text('desc')->nullable();
            $table->timestamps();

            $table->foreign('principal_id')->references('id')->on('users');
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
        Schema::dropIfExists('projects');
    }
}
