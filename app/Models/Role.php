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
        return $this->belongsToMany(User::class, 'role_user');
    }

    public function actions()
    {
        return $this->belongsToMany(Action::class, 'role_action');
    }
}
