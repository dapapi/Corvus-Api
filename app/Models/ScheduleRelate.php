<?php

namespace App\Models;

use App\ModuleUserType;
use App\User;
use Illuminate\Database\Eloquent\Model;


class ScheduleRelate extends Model
{



    protected $fillable = [
        'schedule_id',
        'moduleable_id',
        'moduleable_type',
        'user_id'

    ];

    public function scopeCreateDesc($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
    public function task()
    {
        return $this->hasMany(Task::class, 'id', 'moduleable_id');
        // return $this->belongsTo(Task::class, 'moduleable_id', 'id')->get();
    }
    public function tasktitle()
    {
      //  return $this->hasOne(Task::class, 'id', 'moduleable_id')->pluck('title')->first();
        return $this->hasOne(Task::class, 'id', 'moduleable_id')->pluck('title')->first();
    }
    public function taskid()
    {
        //  return $this->hasOne(Task::class, 'id', 'moduleable_id')->pluck('title')->first();
        return $this->hasOne(Task::class, 'id', 'moduleable_id')->pluck('id')->first();
    }
    public function projectid()
    {

        return $this->hasOne(Project::class, 'id', 'moduleable_id')->pluck('id')->first();
        // return $this->belongsTo(Task::class, 'moduleable_id', 'id')->get();
    }
    public function projecttitle()
    {

        return $this->hasOne(Project::class, 'id', 'moduleable_id')->pluck('title')->first();
        // return $this->belongsTo(Task::class, 'moduleable_id', 'id')->get();
    }
}
