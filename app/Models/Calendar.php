<?php

namespace App\Models;

use App\ModuleUserType;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Calendar extends Model
{
    use SoftDeletes {
        restore as private restoreSoftDeletes;
    }
    //type
    const OPEN = 0;//公开
    const SECRET = 1;//保密
    //status
    const STATUS_NORMAL = 1;//正常
    const STATUS_FROZEN = 2;//冻结

    protected $fillable = [
        'title',
        'color',
        'privacy',
        'starable_id',
        'starable_type',
        'creator_id',
        'principal_id',
        'type',
        'status',
    ];

    public function starable()
    {
        return $this->morphto();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }

    public function participants()
    {
        return $this->morphToMany(User::class, 'moduleable', 'module_users')->wherePivot('type', ModuleUserType::PARTICIPANT);
    }
    public function principal()
    {
        return $this->belongsTo(User::class, 'principal_id', 'id');
    }
    public function schedules()
    {
        return $this->hasMany(Schedule::class,'calendar_id');
    }
}
