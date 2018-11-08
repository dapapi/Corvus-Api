<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    const TYPE_MOVIE = 1; // 影视项目
    const TYPE_VARIETY = 2; // 综艺项目
    const TYPE_ENDORSEMENT = 3; // 商务代言
    const TYPE_PAPI = 4; // papi项目
    const TYPE_BASE = 5; // 基础项目

    protected $fillable = [
        'title',
        'principal_id',
        'creator_id',
        'privacy',
        'priority',
        'status',
        'type',
        'desc',
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
        return $this->morphMany(ModuleUser::class, 'moduleable');
    }

    public function affixes()
    {
        return $this->morphMany(Affix::class, 'affixable');
    }

    public function operateLogs()
    {
        return $this->morphMany(OperateLog::class, 'logable');
    }

    public function fields()
    {
        return $this->hasMany(FieldValue::class);
    }

}
