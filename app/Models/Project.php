<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'title',
        'principal_id',
        'status',
        'type',
        'desc',
    ];

    public function principal()
    {
        return $this->belongsTo(User::class, 'principal_id', 'id');
    }
}
