<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommentEntity extends Model
{

    protected $fillable = [
        'obj',
        'title',
        'start',
        'end',
        'method',
    ];

}
