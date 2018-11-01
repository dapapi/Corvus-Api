<?php

namespace App\Models;

use App\User;
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

    public function scopeCreateDesc($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function logable()
    {
        return $this->morphTo();
    }
}
