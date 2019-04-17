<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AimProject extends Model
{
    protected $table = 'aim_projects';

    protected $fillable = [
        'aim_id',
        'project_id',
        'project_name'
    ];

    public function aim()
    {
        return $this->belongsTo(Aim::class, 'aim_id', 'id');
    }
}
