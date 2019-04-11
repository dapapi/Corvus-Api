<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Period extends Model
{
    use SoftDeletes {
        restore as private restoreSoftDeletes;
    }

    protected $table = 'aim_periods';

    protected $fillable = [
        'name',
        'start_at',
        'end_at',
    ];
}
