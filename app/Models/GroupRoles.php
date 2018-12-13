<?php

namespace App\Models;

use App\ModuleUserType;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GroupRoles extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'id',
        'name',
        'creator_id',
        'update_id',
    ];

    public function Roles()
    {
        return $this->hasMany(Role::class, 'group_id', 'id');
    }

}
