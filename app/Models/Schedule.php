<?php

namespace App\Models;

use App\ModuleUserType;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schedule extends Model
{
    use SoftDeletes {
        restore as private restoreSoftDeletes;
    }

    const OPEN = 0;
    const SECRET = 1;

    const NOREPEAT = 0;
    const DAILY = 1;
    const WEEKLY = 2;
    const MONTHLY = 3;
    //提醒 1:无 2:日程发生时 3:5分钟前 4:10分钟前 5:15分钟前 6:30分钟前 7:1小时前 8:2小时前 9:1天前 10:2天前
    const REMIND_DEFAULT = 1;//无
    const REMIND_ = 2;//日程发生时
    const REMIND_FIVE_MINUTES = 3;//5分钟前
    const REMIND_TEN_MINUTES = 4;//10分钟前
    const REMIND_FIFTEEN_MINUTES = 5;//15分钟前
    const REMIND_THIRTY_MINUTES = 6;//30分钟前
    const REMIND_ONE_HOURS = 7;//1小时前
    const REMIND_TWO_HOURS = 8;//2小时前
    const REMIND_ONE_DAY = 9;//1天前
    const REMIND_TWO_DAY = 10;//2天前

    protected $fillable = [
        'title',
        'calendar_id',
        'is_allday',
        'privacy',
        'start_at',
        'end_at',
        'position',
        'repeat',
        'material_id',
        'creator_id',
        'type',
        'status',
        'desc',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }

    public function calendar()
    {
        return $this->belongsTo(Calendar::class);
    }
    public function affixes()
    {
        return $this->morphMany(Affix::class, 'affixable');
    }
    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function participants()
    {
        return $this->morphToMany(User::class, 'moduleable', 'module_users')->wherePivot('type', ModuleUserType::PARTICIPANT);
    }

    public function schedulerelate()
    {

        return $this->hasMany(ScheduleRelate::class);
    }
}
