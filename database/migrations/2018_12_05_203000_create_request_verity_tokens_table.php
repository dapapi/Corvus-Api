<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRequestVerityTokensTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('request_verity_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->string('token');
            $table->string('device');
            $table->string('telephone')->nullable();
            $table->string('sms_code')->nullable();
            $table->integer('expired_in')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('request_verity_tokens');
    }
}
