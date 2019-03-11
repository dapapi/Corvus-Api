<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterOperateLogsAddField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('operate_logs', function (Blueprint $table) {
            $table->string('field_name',20)->nullable()->comment("标记更改记录的更改的是哪个字段的数据");
            $table->string("title",20)->nullable()->comment('记录修改字段的中文名');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('operate_logs', function (Blueprint $table) {
            //
        });
    }
}
