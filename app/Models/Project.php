<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    const TYPE_BASE = 1;
    const TYPE_MOVIE = 2;
    const TYPE_VARIETY = 3;
    const TYPE_ENDORSEMENT = 4;

    protected $fillable = [
        'title',
        'principal_id',
        'creator_id',
        'privacy',
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

}
