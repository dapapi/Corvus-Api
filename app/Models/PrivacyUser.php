<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class PrivacyUser extends Model
{
    protected $fillable = [
        'user_id',
        'moduleable_id',
        'moduleable_type',
        'moduleable_field',
        'is_privacy'
    ];


}
