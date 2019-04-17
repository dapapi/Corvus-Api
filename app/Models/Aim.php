<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Aim extends Model
{
    use SoftDeletes {
        restore as private restoreSoftDeletes;
    }

    const STATUS_COMPLETE = 1;
    const STATUS_PROCESSING = 0;

    const RANGE_PERSONAL = 1;
    const RANGE_DEPARTMENT = 2;
    const RANGE_COMPANY = 3;

    protected $table = 'aims';

    protected $fillable = [
        'title',
        'range',
        'department_id',
        'department_name',
        'period_id',
        'period_name',
        'type',
        'amount_type',
        'amount',
        'position',
        'talent_level',
        'aim_level',
        'principal_id',
        'principal_name',
        'creator_id',
        'creator_name',
        'percentage',
        'deadline',
        'last_follow_up_at',
        'status',
        'desc',
    ];

    public function parents()
    {
        return $this->hasMany(AimParent::class, 'p_aim_id', 'id');
    }

    public function children()
    {
        return $this->hasMany(AimParent::class, 'c_aim_id', 'id');
    }

    public function operateLogs()
    {
        return $this->morphMany(OperateLog::class, 'logable');
    }

    public function projects()
    {
        return $this->hasMany(AimParent::class, 'c_aim_id', 'id');
    }
}
