<?php

namespace App;

use App\Models\Affix;
use App\Models\Department;
use App\Models\Project;
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

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'icon_url',
        'remember_token',
        'en_name',
        'gender',
        'id_number',
        'phone',
        'political',
        'marriage',
        'cadastral_address',
        'national',
        'current_address',
        'gender',
        'id_number',
        'birth_time',
        'entry_time',
        'blood_type',
        'status',
    ];

    const USER_STATUS_ONE = 1; //
    const USER_STATUS_TOW = 2; //
    const USER_STATUS_THREE = 3; //
    const USER_STATUS_FOUR = 4; //

    const  HIRE_SHAPE_OFFICIAL = 1;  //正式
    const  HIRE_SHAPE_INTERN = 2;   //实习生
    const  HIRE_SHAPE_GUANPEI = 3;   //管培生
    const  HIRE_SHAPE_OUT = 4;      //外包

    const USER_PSWORD = '$2y$10$8D4nCQeQDaCVlPfCveE.2eT4aJyvzxRIQpvpunptdYzGmsQ9hWLJy';

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

    public function findForEmail($email)
    {   var_dump($email);die;

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

    public function participantProjects()
    {
        return $this->morphedByMany(Project::class, 'moduleable', 'module_users')->where('module_users.type', ModuleUserType::PARTICIPANT);
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
