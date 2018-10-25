<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
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
}
