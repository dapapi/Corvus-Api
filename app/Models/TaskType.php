<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'department_id',
        'title',
    ];
}
