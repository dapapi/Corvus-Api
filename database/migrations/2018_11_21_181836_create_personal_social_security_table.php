<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePersonalSocialSecurityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('personal_social_security', function (Blueprint $table) {
            $table->increments('id');
            $table->string('employee_code')->nullable($value = true)->comment('员工号');
            $table->string('id_number',50)->nullable($value = true)->comment('身份证号');
            $table->dateTime('entry_time')->nullable($value = true)->comment('入职时间');
            $table->dateTime('probation_period_time')->nullable($value = true)->comment('试用期时间');
            $table->dateTime('departure_time')->nullable($value = true)->comment('离职时间');
            $table->string('share_department')->nullable($value = true)->comment('分摊部门');
            $table->string('jobs')->nullable($value = true)->comment('岗位');
            $table->string('personnel_category',50)->nullable($value = true)->comment('人员类别');
            $table->string('income_tax_way',20)->nullable($value = true)->comment('个税纳税方式');
            $table->string('wage_standard',50)->nullable($value = true)->comment('工资标准');
            $table->string('attendance',20)->nullable($value = true)->comment('出勤天数');
            $table->string('actual',20)->nullable($value = true)->comment('实际天数');
            $table->string('private_affair',20)->nullable($value = true)->comment('事假天数');
            $table->string('fifty_sick_leave',20)->nullable($value = true)->comment('50%病假天数');
            $table->string('sick_leave')->nullable($value = true)->comment('全薪病假天数');
            $table->string('basic_wage')->nullable($value = true)->comment('基本工资');
            $table->string('attendance_deductions')->nullable($value = true)->comment('考勤扣款');
            $table->string('special')->nullable($value = true)->comment('特殊调整');
            $table->string('violations')->nullable($value = true)->comment('奖惩/违规');
            $table->string('should_send')->nullable($value = true)->comment('应发工资');
            $table->string('social_security')->nullable($value = true)->comment('社保基数');
            $table->string('yang_lao_company')->nullable($value = true)->comment('养老企业');
            $table->string('yang_lao_personal')->nullable($value = true)->comment('养老个人');
            $table->string('health_company')->nullable($value = true)->comment('医保企业');
            $table->string('health_personal')->nullable($value = true)->comment('医保个人');
            $table->string('unemployment_company')->nullable($value = true)->comment('失业企业');
            $table->string('unemployment_personal')->nullable($value = true)->comment('失业个人');
            $table->string('inductrial')->nullable($value = true)->comment('工伤企业');
            $table->string('fertility')->nullable($value = true)->comment('生育企业');
            $table->string('social',50)->nullable($value = true)->comment('社保企业');
            $table->string('social_personal',20)->nullable($value = true)->comment('社保个人');
            $table->string('fund_base',50)->nullable($value = true)->comment('公积金基数');
            $table->string('fund_company',20)->nullable($value = true)->comment('公积金企业');
            $table->string('fund_personal',20)->nullable($value = true)->comment('公积金个人');
            $table->string('payment_company_housing',20)->nullable($value = true)->comment('补缴个人住房');
            $table->string('payment_personal_housing',20)->nullable($value = true)->comment('补缴企业住房');
            $table->string('taxable_wage',20)->nullable($value = true)->comment('应税工资');
            $table->string('scount_wage')->nullable($value = true)->comment('计算工资个税');
            $table->string('reduction')->nullable($value = true)->comment('减免税');
            $table->string('pay_wage')->nullable($value = true)->comment('实付工资');
            $table->string('lay_off_wage')->nullable($value = true)->comment('辞退补偿金');
            $table->string('bank_number')->nullable($value = true)->comment('银行卡号');
            $table->string('bank')->nullable($value = true)->comment('银行');
            $table->string('open_account')->nullable($value = true)->comment('开户行');
            $table->string('second_account')->nullable($value = true)->comment('第二账户标准');
            $table->string('performance')->nullable($value = true)->comment('绩效');
            $table->string('positive_poor')->nullable($value = true)->comment('转正差');
            $table->string('attendance_buckle')->nullable($value = true)->comment('考勤扣款');
            $table->string('second_real')->nullable($value = true)->comment('第二账户实发');
            $table->string('remark')->nullable($value = true)->comment('备注');
            $table->string('service')->nullable($value = true)->comment('中智服务费');
            $table->string('second_payment')->nullable($value = true)->comment('第二账户付款金额');
            $table->string('formalities')->nullable($value = true)->comment('手续费');
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
        Schema::dropIfExists('personal_social_security');
    }
}
