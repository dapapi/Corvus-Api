<?php

namespace App;

use App\Models\Affix;
use App\Models\Department;
use App\Models\Role;
use App\Models\Task;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $appends = [
        'company'
    ];


    public function findForPassport($name)
    {
        $user = User::where('name', $name)
            ->first();
        return $user;
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    public function getCompanyAttribute()
    {
        $department = $this->department()->first();
        if (!$department) {
            return null;
        }
        $company = $this->departmentToCompany($department);
        return $company;
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function Affixex()
    {
        return $this->hasMany(Affix::class);
    }

    public function department()
    {
        return $this->belongsToMany(Department::class);
    }

    public function participantTasks()
    {
        return $this->morphedByMany(Task::class, 'moduleable', 'module_users')->where('module_users.type', ModuleUserType::PARTICIPANT);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    private function departmentToCompany(Department $department)
    {
        $department = $department->pDepartment;
        if ($department->department_pid == 0) {
            return $department;
        } else {
            $this->department($department);
        }
    }
}
