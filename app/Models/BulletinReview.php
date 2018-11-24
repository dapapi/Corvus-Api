<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulletinReview extends Model
{
    protected $fillable = [
        'template_id', // 简报类型
        'member',  //成员
        'title',     //简报周期
        'created_at', //提交时间
        'status',    // 默认0      1  待审批   2 已审批



    ];

    public function scopeCreateDesc($query)
    {

        return $query->orderBy('updated_at');

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
