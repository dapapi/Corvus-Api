<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFilterFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('filter_fields', function (Blueprint $table) {
            $table->increments('id');
            $table->string('table_name')->comment('对应表名');
            $table->unsignedInteger('department_id')->comment('部门id');
            $table->string('code')->comment('对应表中英文');
            $table->string('value')->comment('对应页面中字段中文名');
            $table->tinyInteger('type')->default(1)->comment('前端展示类型,具体对应值,model中维护');
            $table->string('operator')->comment('中间操作符');
            $table->string('content')->comment('下拉列表等提供值');
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
        Schema::dropIfExists('filter_fields');
    }
}
