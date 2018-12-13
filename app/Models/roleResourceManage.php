<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleResourceManage extends Model
{
    protected $table = 'role_resource_manage';

    protected $fillable = [
        'resource_id',
        'data_manage_id',
    ];

    public function users()
    {
        return $this->belongsTo(User::class);
    }

    public function roles()
    {
        return $this->belongsTo(Role::class);
    }
}
