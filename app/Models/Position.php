<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Position extends Model
{
    protected $table = 'position';


    protected $fillable = [
        'id',
        'name',
        'sort',
        'position',


    ];


}
