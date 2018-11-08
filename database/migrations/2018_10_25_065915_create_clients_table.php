<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->increments('id');
            $table->string('company')->unique();
            $table->tinyInteger('type')->default(1);
            $table->tinyInteger('status')->default(1);
            $table->unsignedInteger('industry_id')->nullable();
            $table->string('industry')->nullable();
            $table->tinyInteger('grade')->default(1);
            $table->unsignedInteger('region_id')->nullable();
            $table->string('address')->nullable();
            $table->unsignedInteger('principal_id');
            $table->unsignedInteger('creator_id');
            $table->tinyInteger('size')->nullable();
            $table->string('keyman')->nullable();
            $table->text('desc')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('region_id')->references('id')->on('regions');
            $table->foreign('industry_id')->references('id')->on('industries');
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
        Schema::dropIfExists('clients');
    }
}
