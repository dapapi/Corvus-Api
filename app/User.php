<?php

namespace App;

use App\Models\Affix;
use App\Models\Department;
use App\Models\PersonalSkills;
use App\Models\PersonalDetail;
use App\Models\PersonalJob;
use App\Models\PersonalSalary;
use App\Models\OperateLog;
use App\Models\Education;
use App\Models\Training;
use App\Models\Record;
use App\Models\FamilyData;


use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;


class User extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $table = 'users';

    protected $fillable = [
        'name',
        'icon_url',
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
        'hire_shape',
        'archive_time',
        'position',
        'position_type',
        'entry_status',
        'high_school',
        'age',
        'jobs',
        'number',
        'work_email',
        'high_school',
        'age',
        'jobs',
        'number',
        'work_email',
        'disable',

    ];

    const USER_STATUS_DEFAULT = 0; // 默认
    //状态
    const USER_STATUS_TRIAL = 1;    //试用期
    const USER_STATUS_POSITIVE = 2; //转正
    const USER_STATUS_DEPARTUE = 3; //离职
    const USER_STATUS_INTERN = 4;   //实习

    //聘用形式
    const  HIRE_SHAPE_OLABOR = 1;      // 劳务
    const  HIRE_SHAPE_LOWE = 2;        // 劳动
    const  HIRE_SHAPE_INTERNSHIP = 3;  // 实习
    const  HIRE_SHAPE_OUT = 4;         // 外包

    //状态 在职 离职 全部
    const  USER_POSITIVE = 1;//在职
    const  USER_DEPARTUE = 2; //离职

    const   USER_ARCHIVE = 5; //归档

    const  USER_TYPE_DISABLE = 2; //禁用
    const  USER_ENTRY_STATUS = 3; //hr审核状态已同意

    const  USER_DEPARTMENT_DEFAULT = 1; //hr审核通过 默认职位未分配职位



    const USER_PSWORD = '$2y$10$61FPobZVqUNUuaHlse2yFOlacwYA272Am0w/0x0Fv8vq1JfOzu3LK';

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


    // todo 可能再加用户状态筛选
    public function findForPassport($name)
    {
        $user = User::where('name', $name)
            ->orWhere('phone', $name)
            ->orWhere('email', $name)
            ->first();
        return $user;
    }

    public function findForEmail($email)
    {
        var_dump($email);
        die;

        return $user;
    }

    public function setPasswordAttribute($value)
    {
        if(isset($value)){
            $this->attributes['password'] = Hash::make($value);
        }else{
            $this->attributes['password'] = '';
        }
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

    public function getIsDepartmentPrincipalAttribute()
    {
        $department = $this->department()->wherePivot('type', 1)->first();

        if ($department)
            return 1;
        else {
            return 0;
        }
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

    public function skills()
    {
        return $this->hasMany(PersonalSkills::class, 'user_id', 'id');
    }

    public function personalDetail()
    {
        return $this->hasOne(PersonalDetail::class, 'user_id', 'id');
    }

    public function personalJob()
    {
        return $this->hasOne(PersonalJob::class, 'user_id', 'id');
    }

    public function PersonalSalary()
    {
        return $this->hasOne(PersonalSalary::class, 'user_id', 'id');
    }

    public function education()
    {
        return $this->hasMany(Education::class, 'user_id', 'id');
    }

    public function Training()
    {
        return $this->hasMany(Training::class, 'user_id', 'id');
    }

    public function Record()
    {
        return $this->hasMany(Record::class, 'user_id', 'id');
    }

    public function FamilyData()
    {
        return $this->hasMany(FamilyData::class, 'user_id', 'id');
    }

    public function operateLogs()
    {
        return $this->morphMany(OperateLog::class, 'logable');
    }


    private function departmentToCompany(Department $department)
    {
        if ($department->department_pid == 0) {
            return $department;
        } else {
            $pDepartment = $department->pDepartment;
            if ($pDepartment)
                return $this->departmentToCompany($pDepartment);
            else
                return $department;
        }
    }

}
