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
}
