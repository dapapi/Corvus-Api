<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApprovalTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('approval_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('approval_id');
            $table->unsignedInteger('element_id');
            $table->string('title')->nullable();
            $table->string('hint')->nullable();
            $table->text('content')->nullable();
            $table->unsignedTinyInteger('sort');
            $table->unsignedInteger('pid');
            $table->tinyInteger('type')->default(0)->comment('展示类型');
            $table->tinyInteger('status')->default(0)->comment('0:不启用,1:启用');
            $table->boolean('required')->default(0);
            $table->timestamps();

            $table->foreign('approval_id')->references('id')->on('approvals');
            $table->foreign('element_id')->references('id')->on('form_elements');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('approval_templates');
    }
}
