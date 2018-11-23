<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePersonalSkillsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('personal_skills', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('language_level')->nullable($value = true)->comment('单位名称');
            $table->string('certificate')->nullable($value = true)->comment('所获证书');
            $table->string('computer_level')->nullable($value = true)->comment('计算机等级');
            $table->string('specialty')->nullable($value = true)->comment('个人特长');
            $table->tinyInteger('disease')->default(0)->comment('是否患基本');
            $table->tinyInteger('pregnancy')->default(0)->comment('是否怀孕');
            $table->tinyInteger('migration')->default(0)->comment('是否同意工作迁移');
            $table->text('remark')->nullable($value = true)->comment('备注');
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
        Schema::dropIfExists('personal_skills');
    }
}
