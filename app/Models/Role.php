<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'name',
        'group_id',
        'description'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'role_users');
    }

    public function actions()
    {
        return $this->belongsToMany(Action::class, 'role_action');
    }

    public function Training()
    {
        return $this->hasMany(Training::class, 'user_id', 'id');
    }
}
