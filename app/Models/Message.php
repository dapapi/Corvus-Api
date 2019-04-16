<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'module',
        'title',
        'link'
    ];
    const STAR = 207;//艺人
    const BLOGGER = 208;//博主
    const TRAILS = 209;//线索
    const CLIENT = 210;//客户
    const PROJECT = 211;//项目
    const TASK = 212;//任务
    const CONTRACT = 213;//合同
    const CALENDAR = 214;//日历
    const ATTENDANCE = 215;//
    const APPROVAL = 216;//审批
    const REPOSITORY = 217;//知识库
    const ANNOUNCENMENT = 218;//公告
    const REPORT = 219;//简报
    const PERSONNELMANAGE = 220;//人事

    const HAS_READ = 2;//已读
    const UN_READ = 1;//未读
    public function data()
    {
        return $this->hasMany(MessageData::class,"message_id",'id');
    }
    public function recive()
    {
        return $this->hasMany(MessageState::class,'message_id','id');
    }
}
