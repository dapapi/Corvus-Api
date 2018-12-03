<?php

namespace App\Models;

use App\ModuleUserType;
use App\OperateLogMethod;
use App\Traits\OperateLogTrait;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes {
        restore as private restoreSoftDeletes;
    }
    use OperateLogTrait;

    const TYPE_MOVIE = 1; // 影视项目
    const TYPE_VARIETY = 2; // 综艺项目
    const TYPE_ENDORSEMENT = 3; // 商务代言
    const TYPE_PAPI = 4; // papi项目
    const TYPE_BASE = 5; // 基础项目

    const STATUS_NORMAL = 1; // 进行中
    const STATUS_COMPLETE = 2; // 完成
    const STATUS_FROZEN = 3; // 终止
    const STATUS_DEL = 4; // 删除

    protected $fillable = [
        'title',
        'principal_id',
        'creator_id',
        'trail_id',
        'privacy',
        'priority',
        'status',
        'type',
        'desc',
        'start_at',
        'end_at',
        'complete_at',
        'stop_at',
        'delete_at'
    ];

    public function principal()
    {
        return $this->belongsTo(User::class, 'principal_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }

    public function participants()
    {
        return $this->morphToMany(User::class, 'moduleable', 'module_users')->wherePivot('type', ModuleUserType::PARTICIPANT);
    }

    public function affixes()
    {
        return $this->morphMany(Affix::class, 'affixable');
    }


    public function fields()
    {
        return $this->hasMany(FieldValue::class);
    }

    public function tasks()
    {
        return $this->morphToMany(Task::class, 'resourceable','task_resources');
    }

    public function trail()
    {
        return $this->belongsTo(Trail::class);
    }

    public function relateProjects()
    {
        return $this->morphedByMany(Project::class, 'moduleable', 'project_relates');
    }

    public function relateTasks()
    {
        return $this->morphedByMany(Task::class, 'moduleable', 'project_relates');
    }
}
