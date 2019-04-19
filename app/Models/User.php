<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
    protected $fillable = [
        'name',
        
    ];

    public function platformable()
    {
        return $this->morphTo();
    }

}
