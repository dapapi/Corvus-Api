<?php

namespace App\Models;

use App\OperateLogMethod;
use App\Traits\OperateLogTrait;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use SoftDeletes {
        restore as private restoreSoftDeletes;
    }

    use OperateLogTrait;

    const TYPE_MOVIE = 1; // 影视项目
    const TYPE_VARIETY = 2; // 综艺项目
    const TYPE_ENDORSEMENT = 3; // 商务代言
    const TYPE_PAPI = 4; // papi项目

    const SIZE_LISTED = 1;
    const SIZE_TOP500 = 2;

    const GRADE_NORMAL = 1;
    const GRADE_PROXY = 2;

    const STATUS_NORMAL = 1;
    const STATUS_FROZEN = 2;

    protected $fillable = [
        'company',
        'grade',             // 级别
        'province',
        'city',
        'district',
        'address',
        'principal_id',
        'creator_id',
        'size',             // 规模
        'keyman',           // 决策关键人
        'desc',
        'type',             // 商务客户
        'status',
    ];

    protected $dates = ['deleted_at'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }

    public function principal()
    {
        return $this->belongsTo(User::class, 'principal_id', 'id');
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function tasks()
    {
        return $this->morphToMany(Task::class, 'resourceable','task_resources');
    }

    public function affixes()
    {
        return $this->morphMany(Affix::class, 'affixable');
    }
}
