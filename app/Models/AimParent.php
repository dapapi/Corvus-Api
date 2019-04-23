<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AimParent extends Model
{
    protected $table = 'aim_aims';

    protected $fillable = [
        'p_aim_id',
        'p_aim_name',
        'p_aim_range',
        'c_aim_id',
        'c_aim_name',
        'c_aim_range',
    ];
}
