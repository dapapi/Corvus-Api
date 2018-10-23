<?php

namespace App\Models;

use App\Http\Controllers\TestController;
use App\User;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'title',
        'type',
        'task_pid',
        'creator_id',
        'principal_id',
        'status',
        'priority',
        'desc',
        'start_at',
        'end_at',
        'complete_at',
        'stop_at'
    ];

    public function pTask()
    {
        return $this->belongsTo(Task::class, 'task_pid', 'id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'id', 'task_pid');
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
}
