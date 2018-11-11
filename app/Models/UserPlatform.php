<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserPlatform extends Model
{
    protected $fillable = [
        'user_id',
        'platformable_id',
        'platformable_type',
        'platform_id',
        'status'
    ];

    public function platformable()
    {
        return $this->morphTo();
    }

}
