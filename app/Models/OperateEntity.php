<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperateEntity extends Model
{

    protected $fillable = [
        'obj',
        'title',
        'start',
        'end',
        'method',
        'field_name',
        'field_title',
    ];

}
