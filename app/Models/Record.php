<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Record extends Model
{
    protected $table = 'record';


    protected $fillable = [
        'user_id',
        'unit_name',
        'department',
        'position',
        'entry_time',
        'departure_time',
        'monthly_pay',
        'departure_why',

    ];


}
