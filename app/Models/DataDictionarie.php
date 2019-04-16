<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\User;


class GroupList extends Model
{
    protected $table = 'role_users';

    protected $fillable = [
        'role_id',
        'user_id',

    ];


    public function users()
    {
        return $this->hasMany(User::class, 'id', 'user_id');
    }


}
