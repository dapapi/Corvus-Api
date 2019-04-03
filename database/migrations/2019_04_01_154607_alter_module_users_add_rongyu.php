<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterModuleUsersAddRongyu extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('module_users', function (Blueprint $table) {
            $table->string("user_name")->comment('经纪人，参与人，宣传人，制作人名字')->nullable();
            $table->integer('department_id')->comment('经纪人，参与人，宣传人，制作人，部门')->nullable();
            $table->integer('department_name')->comment('经纪人，参与人，宣传人，制作人，部门名称')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('module_users', function (Blueprint $table) {
            //
        });
    }
}
