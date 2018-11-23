<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePersonalJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('personal_jobs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('rank',50)->nullable($value = true)->comment('职级');
            $table->string('eport',50)->nullable($value = true)->comment('汇报对象');
            $table->dateTime('positive_time')->nullable($value = true)->comment('转正时间');
            $table->string('management',20)->nullable($value = true)->comment('管理形式');
            $table->string('siling',50)->nullable($value = true)->comment('司龄');
            $table->dateTime('first_work_time')->nullable($value = true)->comment('首次参加工作时间');
            $table->string('modulation_siling',50)->nullable($value = true)->comment('调整司龄');
            $table->string('work_ling',50)->nullable($value = true)->comment('工龄');
            $table->string('modulation_work_ling',50)->nullable($value = true)->comment('调整工龄');
            $table->string('subordinate_sum',50)->nullable($value = true)->comment('直属下属数量');
            $table->string('work_city',50)->nullable($value = true)->comment('工作城市');
            $table->string('taxcity',50)->nullable($value = true)->comment('纳税城市');
            $table->dateTime('contract_start_time')->nullable($value = true)->comment('现合同开始时间');
            $table->dateTime('contract_end_time')->nullable($value = true)->comment('现合同结束时间');
            $table->string('recruitment_ditch',50)->nullable($value = true)->comment('招聘渠道');
            $table->string('recruitment_type',20)->nullable($value = true)->comment('校招/社招');
            $table->string('other_ditch',50)->nullable($value = true)->comment('其他招聘渠道');
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('personal_jobs');
    }
}
