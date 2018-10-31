<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperateLog extends Model
{
    protected $fillable = [
        'user_id',
        'logable_id',
        'logable_type',
        'content',
        'method',
        'status',
        'level'
    ];

}
