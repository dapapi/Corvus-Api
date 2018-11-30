<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;


class AnnouncementScope extends Model
{

    protected $table =  'announcement_scope';
    protected $fillable = [
        'announcement_id',
        'department_id'

    ];

    public function scopeCreateDesc($query)
    {

        return $query->orderBy('id');

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
