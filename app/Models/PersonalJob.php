<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class PersonalJob extends Model
{
    use SoftDeletes;

    protected $table = 'personal_jobs';


    protected $fillable = [
        'id',
        'user_id',
        'rank',//'职级');
        'eport',//'汇报对象');
        'positive_time',//'转正时间');
        'management',//'管理形式');
        'siling',//'司龄');
        'first_work_time',//'首次参加工作时间');
        'modulation_siling',//'调整司龄');
        'work_ling',//'工龄');
        'modulation_work_ling',//'调整工龄');
        'subordinate_sum',//'直属下属数量');
        'work_city',//'工作城市');
        'taxcity',//'纳税城市');
        'contract_start_time',//'现合同开始时间');
        'contract_end_time',//'现合同结束时间');
        'recruitment_ditch',//'招聘渠道');
        'recruitment_type',//'校招/社招');
        'other_ditch',//'其他招聘渠道');

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
