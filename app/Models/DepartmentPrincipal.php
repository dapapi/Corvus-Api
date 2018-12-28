<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class DepartmentPrincipal extends Model
{
    protected $fillable = [
        'department_id',
        'user_id',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'department_id', 'id');
    }
}
