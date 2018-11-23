<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Education extends Model
{
    protected $table = 'education';


    protected $fillable = [
        'user_id',
        'school',
        'specialty',
        'start_time',
        'end_time',
        'degree',
        'graduate',

    ];




    const USER_STATUS_ONE = 1; //
    const USER_STATUS_TOW = 2; //
    const USER_STATUS_THREE = 3; //
    const USER_STATUS_FOUR = 4; //

    const SIZE_NORMAL = 1;
    const SIZE_LISTED = 2;
    const SIZE_TOP500 = 3;

    const STATUS_NORMAL = 1;
    const STATUS_FROZEN = 2;




}
