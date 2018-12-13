<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleResourceView extends Model
{
    protected $table = 'role_resource_view';

    protected $fillable = [
        'resource_id',
        'data_view_id',


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
