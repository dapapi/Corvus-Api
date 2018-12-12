<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleResource extends Model
{
    protected $table = 'role_resources';

    protected $fillable = [
        'role_id',
        'resouce_id',
        'updated_at',
        'created_at',

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
