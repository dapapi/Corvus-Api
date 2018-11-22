<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalDetail extends Model
{
    protected $table = 'personal_detail';


    protected $fillable = [
        'user_id',
        'id_card_url' , //'身份证url',
        'passport_code',//护照号',
        'id_number',//身份证号',
        'card_number_one',//工资银行卡号1
        'card_number_two',//工资银行卡号2'
        'credit_card', //信用卡'
        'accumulation_fund',//公积金',
        'opening',//开户行',
        'last_company',//上家公司',
        'responsibility' ,//岗位职责',
        'contract',//合同
        'address',//地址
        'entry_time',

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
