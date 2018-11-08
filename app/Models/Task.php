<?php

namespace App\Models;

use App\ModuleUserType;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'type_id',
        'task_pid',
        'creator_id',
        'principal_id',
        'status',
        'priority',
        'desc',
        'privacy',
        'start_at',
        'end_at',
        'complete_at',
        'stop_at',
        'deleted_at',
    ];

    public function scopeCreateDesc($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function pTask()
    {
        return $this->belongsTo(Task::class, 'task_pid', 'id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'task_pid', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }

    public function principal()
    {
        return $this->belongsTo(User::class, 'principal_id', 'id');
    }

    public function resource()
    {
        return $this->hasOne(TaskResource::class);
    }

    public function affixes()
    {
        return $this->morphMany(Affix::class, 'affixable');
    }

    public function operateLogs()
    {
        return $this->morphMany(OperateLog::class, 'logable');
    }

    public function participants()
    {
        return $this->morphToMany(User::class, 'moduleable', 'module_users')->where('module_users.type', ModuleUserType::PARTICIPANT);
    }

    public function type()
    {
        return $this->belongsTo(TaskType::class);
    }

}
