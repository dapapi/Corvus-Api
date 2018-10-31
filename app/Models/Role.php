<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'desc'
    ];

    public function users()
    {
        return $this->hasManyThrough(User::class, RoleUser::class, 'role_id', 'id', 'user_id');
    }

    public function actions()
    {
        return $this->hasManyThrough(Action::class, RoleUser::class, 'role_id', 'id', 'action_id');
    }
}
