<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProjectTemplateFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_template_fields', function (Blueprint $table) {
            $table->increments('id');
            $table->string('key');
            $table->tinyInteger('field_type');
            $table->string('content');
            $table->tinyInteger('module_type');
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('is_secret')->default(1);
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
        Schema::dropIfExists('project_template_fields');
    }
}
