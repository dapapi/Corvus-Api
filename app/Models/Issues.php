<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Issues extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'issues', // 问题
        'department_id',  //部门
        'member_id',  //成员
        //'bulletin_id', //bulletin
        'task_id',  //   选择任务标识
        'type', //类型  1.文本  2.数字 3.日期  4.任务 5.附件
        'required',  //默认  1.必填   0.不填
        'accessory', //模板对象id


    ];
    protected $dates = ['deleted_at'];
    public function scopeCreateDesc($query)
    {

        return $query->orderBy('updated_at','DESC');

    }

    public function creator()
    {

        return $this->belongsTo(User::class, 'creator_id', 'id');
    }

    public function affixes()
    {

        return $this->morphMany(Affix::class, 'affixable');
    }

    public function operateLogs()
    {
        return $this->morphMany(OperateLog::class, 'logable');
    }

    public function tasks()
    {

        return $this->morphToMany(Task::class, 'resourceable','task_resources');
    }

    public function broker()
    {
        return $this->belongsTo(User::class, 'broker_id', 'id');

    }

}
