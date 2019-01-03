<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRegistTokensTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('regist_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->string('token');
            $table->integer('expired_in');
            $table->string('tokenable_type');
            $table->string('tokenable_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('regist_tokens');
    }
}
