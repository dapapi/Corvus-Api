<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterStarsAddAccodeEnflag extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stars', function (Blueprint $table) {
            $table->string("accode")->comment('艺人编码')->nullable();
            $table->integer("enflag")->comment("2=已起用 3=已停用")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stars', function (Blueprint $table) {
            //
        });
    }
}
