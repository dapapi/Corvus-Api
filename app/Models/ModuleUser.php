<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class ModuleUser extends Model
{
    protected $fillable = [
        'user_id',
        'moduleable_id',
        'moduleable_type',
        'type'
    ];

    public function moduleable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
