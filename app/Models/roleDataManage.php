<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleDataManage extends Model
{
    protected $table = 'role_data_manage';

    protected $fillable = [
        'role_id',
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
