<?php

namespace App\Http\Transformers;

use App\Models\Department;
use App\User;
use League\Fractal\TransformerAbstract;
use Illuminate\Support\Facades\DB;
use App\Models\DepartmentPrincipal;

class UserTransformer extends TransformerAbstract
{
    protected $availableIncludes = [
        'department',
        'skills',
        'detail',
        'job',
        'salary',
        'operateLogs',
        'education',
        'training',
        'record',
        'familyData',
        'roleUser',
        'tasks',
        'schedules',
        'position'
    ];
   // protected $defaultIncludes = ['detail','job','salary'];
    public function transform(User $user)
    {
        //假头像
        $sub_str = substr($user->icon_url,0,1);
        if ($sub_str == "#" || $sub_str == null){
            $user->icon_url = "https://res-crm.papitube.com/image/artist-no-avatar.png";
        }
        $array = [
            'id' => hashid_encode($user->id),
            'phone' => $user->phone,
            'email' => $user->email,
            'birth_time' => $user->birth_time,
            'name' => $user->name,
            'current_address' => $user->current_address,
            'status' => $user->status,
            'department' => $user->department,
            'position' => $user->position,
            'hire_shape' => $user->hire_shape,
            'entry_time' => $user->entry_time,
            'archive_time' => $user->archive_time,
            'position_type' => $user->position_type,
            'en_name' => $user->en_name, // '英文',
            'gender' => $user->gender,//性别',
            'id_number' => $user->id_number,//'身份证号',
            'political' => $user->political,//'政治面貌',
            'marriage' => $user->marriage,//'婚姻状态',
            'cadastral_address' => $user->cadastral_address,//'户籍地址',
            'national' => $user->national,// '民族',
            'blood_type' => $user->blood_type,// '血型',
            'icon_url' => $user->icon_url,//'用户头像',
            'high_school' => $user->high_school,// '最高学历',
            'age' => $user->age,//'年龄',
            'jobs' => $user->jobs,//'岗位',
            'number' => $user->number,//'工号',
            'work_email' => $user->work_email,//'工作邮箱',
            'disable' => $user->disable,
            'entry_status' => $user->entry_status,
            'organization_id'   =>  $user->department()->first()->company_id
        ];

        $companyInfo = DB::table('department_user as du')//
            ->join('departments as ds', function ($join) {
                $join->on('du.department_id', '=', 'ds.id');
            })
            ->join('data_dictionaries as dds', function ($join) {
                $join->on('dds.id', '=', 'ds.company_id');
            })
            ->where('du.user_id', $user->id)
            ->select('ds.company_id', 'dds.name')->first();

        if ($companyInfo) {

            $array['company'] = $companyInfo->name;
            $array['company_id'] = hashid_encode($companyInfo->company_id);
        }

        $principalInfo = DB::table('department_principal')->where('department_principal.user_id', $user->id)->count();
        if ($principalInfo) {
            $array['is_department_principal'] = 1;
        }else{
            $array['is_department_principal'] = 0;
        }

        $data = DB::table('department_user as du')
            ->join('departments as ds', function ($join) {
                $join->on('du.department_id', '=', 'ds.id');
            })
            ->select('ds.name','ds.id')
            ->where('user_id', $user->id)->first();

        $array['department'] = $data;

        return $array;
    }

    public function includeDepartment(User $user)
    {
        $department = $user->department()->first();
        if (!$department) {
            return null;
        }
        return $this->item($department, new DepartmentTransformer());
    }

    public function includeSkills(User $user)
    {
        $skills = $user->skills;

        return $this->collection($skills, new SkillTransformer());
    }
    //关联个人信息表
    public function includeDetail(User $user)
    {
        $detail = $user->personalDetail;
        if(!$detail)
            return null;

        return $this->item($detail, new DetailTransformer());
    }

    public function includeJob(User $user)
    {
        $job = $user->personalJob;
        if(!$job)
            return null;

        return $this->item($job, new JobTransformer());
    }

    public function includeSalary(User $user)
    {
        $salary = $user->personalSalary;
        if(!$salary)
            return null;

        return $this->item($salary, new SalaryTransformer());
    }

    public function includeOperateLogs(User $user)
    {
        $log = $user->operateLogs;

        return $this->collection($log, new OperateLogTransformer());
    }
    public function includeTasks(User $user)
    {

        $tasks = $user->userTasks;
        return $this->collection($tasks, new TaskTransformer());
    }
    public function includeSchedules(User $user)
    {

        $schedules= $user->userSchedules;

        return $this->collection($schedules, new ScheduleTransformer());
    }
    public function includeEducation(User $user)
    {
        $education = $user->education;

        return $this->collection($education, new EducationTransformer());
    }

    public function includeTraining(User $user)
    {
        $training = $user->training;

        return $this->collection($training, new TrainingTransformer());
    }

    public function includeRecord(User $user)
    {
        $record = $user->record;

        return $this->collection($record, new RecordTransformer());
    }

    public function includeFamilyData(User $user)
    {
        $familyData = $user->familyData;

        return $this->collection($familyData, new FamilyDataTransformer());
    }
    public function includeRoleUser(User $user)
    {
        $roleUserData = $user->roleUser;

        return $this->collection($roleUserData, new RoleUserTransformer());
    }
    public function includePosition(User $user)
    {
        $position = $user->position;
        if (!$position)
            return null;

        return $this->item($position, new PositionTransformer());
    }

}