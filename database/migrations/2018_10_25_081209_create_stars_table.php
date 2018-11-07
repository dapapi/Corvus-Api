<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStarsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stars', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->text('desc')->nullable();
            $table->unsignedInteger('broker_id')->nullable();
            $table->string('avatar')->nullable();
            $table->tinyInteger('gender')->default(1);
            $table->date('birthday')->nullable();
            $table->string('phone')->nullable();
            $table->string('wechat')->nullable();
            $table->string('email')->nullable();
            $table->tinyInteger('source')->nullable();
            $table->tinyInteger('communication_status')->nullable();
            $table->boolean('intention')->default(false);
            $table->string('intention_desc')->nullable();
            $table->boolean('sign_contract_other')->default(false);
            $table->string('sign_contract_other_name')->nullable();
            $table->date('sign_contract_at')->nullable();
            $table->tinyInteger('sign_contract_status')->default(1);
            $table->date('terminate_agreement_at')->nullable();
            $table->unsignedInteger('creator_id');
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('type')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('broker_id')
                ->references('id')
                ->on('users');

            $table->foreign('creator_id')
                ->references('id')
                ->on('users');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stars');
    }
}
