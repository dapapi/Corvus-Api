<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBloggersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bloggers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nickname');
            $table->tinyInteger('communication_status')->nullable();
            $table->boolean('intention')->default(false);
            $table->string('intention_desc')->nullable();
            $table->date('sign_contract_at')->nullable();
            $table->tinyInteger('level')->nullable();
            $table->date('hatch_star_at')->nullable();
            $table->date('hatch_end_at')->nullable();
            $table->unsignedInteger('producer_id')->nullable();
            $table->tinyInteger('sign_contract_status')->default(1);
            $table->text('desc')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('type')->default(1);
            $table->string('avatar')->nullable();
            $table->unsignedInteger('creator_id');
            $table->tinyInteger('gender')->default(1);
            $table->string('cooperation_demand')->nullable();
            $table->date('terminate_agreement_at')->nullable();
            $table->boolean('sign_contract_other')->default(false);
            $table->string('sign_contract_other_name')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('producer_id')
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
        Schema::dropIfExists('bloggers');
    }
}
