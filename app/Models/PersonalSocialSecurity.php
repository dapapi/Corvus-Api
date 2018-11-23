<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class PersonalSocialSecurity extends Model
{
    use SoftDeletes;

    protected $table = 'personal_social_security';


    protected $fillable = [
        'id',
        'user_id',
        'employee_code',//员工号');
        'id_number',//'身份证号');
        'entry_time',//'入职时间');
        'probation_period_time',//'试用期时间');
        'departure_time',//'离职时间');
        'share_department',//'分摊部门');
        'jobs',//'岗位');
        'personnel_category',//'人员类别');
        'income_tax_way',//'个税纳税方式');
        'wage_standard',//'工资标准');
        'attendance',//'出勤天数');
        'actual',//'实际天数');
        'private_affair',//'事假天数');
        'fifty_sick_leave',//'50%病假天数');
        'sick_leave',//'全薪病假天数');
        'basic_wage',//'基本工资');
        'attendance_deductions',//'考勤扣款');
        'special',//'特殊调整');
        'violations',//'奖惩/违规');
        'should_send',//'应发工资');
        'social_security',//'社保基数');
        'yang_lao_company',//'养老企业');
        'yang_lao_personal',//'养老个人');
        'health_company',//'医保企业');
        'health_personal',//'医保个人');
        'unemployment_company',//'失业企业');
        'unemployment_personal',//'失业个人');
        'inductrial',//'工伤企业');
        'fertility',//'生育企业');
        'social',//'社保企业');
        'social_personal',//'社保个人');
        'fund_base',//'公积金基数');
        'fund_company',//'公积金企业');
        'fund_personal',//'公积金个人');
        'payment_company_housing',//'补缴个人住房');
        'payment_personal_housing',//'补缴企业住房');
        'taxable_wage',//'应税工资');
        'scount_wage',//'计算工资个税');
        'reduction',//'减免税');
        'pay_wage',//'实付工资');
        'lay_off_wage',//'辞退补偿金');
        'bank_number',//'银行卡号');
        'bank',//'银行');
        'open_account',//'开户行');
        'second_account',//'第二账户标准');
        'performance',//'绩效');
        'positive_poor',//'转正差');
        'attendance_buckle',//'考勤扣款');
        'second_real',//'第二账户实发');
        'remark',//'备注');
        'service',//'中智服务费');
        'second_payment',//'第二账户付款金额');
        'formalities',//'手续费');

    ];




    const USER_STATUS_ONE = 1; //
    const USER_STATUS_TOW = 2; //
    const USER_STATUS_THREE = 3; //
    const USER_STATUS_FOUR = 4; //

    const SIZE_NORMAL = 1;
    const SIZE_LISTED = 2;
    const SIZE_TOP500 = 3;

    const STATUS_NORMAL = 1;
    const STATUS_FROZEN = 2;




}
