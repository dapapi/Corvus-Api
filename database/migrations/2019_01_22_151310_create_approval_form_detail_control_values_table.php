<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApprovalFormDetailControlValuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('approval_form_detail_control_values', function (Blueprint $table) {
            $table->increments('id');
            $table->string('form_instance_number');
            $table->unsignedInteger('form_control_id');
            $table->string('value');
            $table->unsignedInteger('sort_number');
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
        Schema::dropIfExists('approval_form_detail_control_values');
    }
}
