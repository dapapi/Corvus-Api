<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FamilyData extends Model
{
    protected $table = 'family_data';


    protected $fillable = [
        'user_id',
        'name',
        'relation',
        'position',
        'birth_time',
        'work_units',
        'position',
        'contact_phone',

    ];


}
