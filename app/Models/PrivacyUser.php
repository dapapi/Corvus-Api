<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\User;

class PrivacyUser extends Model
{
    protected $fillable = [
        'user_id',
        'moduleable_id',
        'moduleable_type',
        'moduleable_field',
        'is_privacy'
    ];
    public function user()
    {
        return $this->belongsTo(User::class,'user_id','id');
    }

}
