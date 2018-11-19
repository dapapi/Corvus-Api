<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalSkills extends Model
{
    protected $table = 'personal_skills';


    protected $fillable = [
        'user_id',
        'language_level',//外语水平
        'certificate',//所获证书
        'computer_level',//计算机等级
        'specialty',//个人特长
        'disease',//是否患基本
        'pregnancy',//是否怀孕
        'migration',//是否同意工作迁移
        'remark',//备注

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
