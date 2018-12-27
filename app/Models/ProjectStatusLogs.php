<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;


class ProjectStatusLogs extends Model
{

    protected $fillable = [
        'user_id',
        'logable_id',
        'logable_type',
        'content',
        'status',

    ];
    public function scopeCreateDesc($query)
    {

        return $query->orderBy('created_at');

    }

}
