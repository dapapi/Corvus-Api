<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class DepartmentUser extends Model
{
    protected $table = 'department_user';
    protected $fillable = [
        'department_id',
        'user_id',
        'type',
    ];



    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }
    public function users()
    {
        return $this->hasManyThrough(User::class, DepartmentUser::class, 'department_id', 'id', 'id', 'user_id');
    }
}
