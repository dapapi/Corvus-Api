<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use SoftDeletes;
    //申请类型常量 申请类型 1:请假 2:加班 3:出差 4:外勤
    const LEAVE = 1; //请假
    const OVERTIME = 2;//加班
    const BUSINESS_TRAVEL =3;//出差
    const FIELD_OPERATION = 4;//外勤

    //TODO 以后设置为字典
    //请假类型常量
    //请假类型 1:事假，2:病假，3:调休假，4:年假，5:婚假，6:产假，7:陪产假，8:丧假，9:其他
    const CASUAL_LEAVE = 1;//事假
    const SICK_LEAVE = 2;//病假
    const LEAVE_IN_LIEU = 3;//调休假
    const ANNUAL_LEAVE = 4;//年假
    const MARRIAGE_LEAVE = 5;//婚假
    const MATERNITY_LEAVE = 6;//产假
    const PATERNITY_LEAVE = 7;//陪产假
    const FUNERAL_LEAVE = 8;//丧假
    const OTHER_LEAVE = 9;//其他

    //状态 1:已同意  2:待审批  3:已拒绝  4:已作废
    const APPROVAL_PENFING = 1;//待审批
    const AGREED = 2;//已同意
    const REFUSED = 3;//已拒绝
    const INVALID = 4;//已作废


    protected $fillable = [
        'type',
        'start_at',
        'end_at',
        'number',
        'cause',
        'affixes',
        'approval_flow',
        'notification_person',
        'creator_id',
        'leave_type',
        'place',
    ];
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }

}
