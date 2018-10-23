<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = [
        'name',
        'department_pid',
        'desc',
    ];

    public function pDepartment()
    {
        return $this->belongsTo(Department::class, 'department_pid', 'id');
    }

    public function departments()
    {
        return $this->hasMany(Department::class, 'department_pid', 'id');
    }

    public function users()
    {
        return $this->hasManyThrough(User::class, DepartmentUser::class, 'department_id', 'id', 'id', 'user_id');
    }
}
