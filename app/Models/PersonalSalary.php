<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class PersonalSalary extends Model
{
    use SoftDeletes;

    protected $table = 'personal_salary';


    protected $fillable = [
        'id',
        'user_id',
        'entry_time',//'入职时间');
        'trial_end_time',//'试用期截止时间');
        'pdeparture_time',//'离职时间');
        'share_department',//'分摊部门');
        'jobs',//'岗位');
        'income_tax',//'个税纳税方式');
        'personnel_category',//'人员类别');

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
