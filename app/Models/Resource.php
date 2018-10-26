<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    protected $fillable = [
        'title',
        'type',
        'status',
        'desc'
    ];

    public function taskResource()
    {
        return $this->hasMany(TaskResource::class);
    }
}
