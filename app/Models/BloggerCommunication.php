<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BloggerCommunication extends Model
{
    protected $table = 'communication';
    protected $fillable = [
        'name',
    ];
}
