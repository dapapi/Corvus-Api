<?php

namespace App\Http\Transformers;

use App\Models\Department;
use App\User;
use League\Fractal\TransformerAbstract;
use Illuminate\Support\Facades\DB;


class UserTransformer extends TransformerAbstract
{
    protected $availableIncludes = [
        'department',
        'skills',
        'detail',
        'job',
        'salary',
        'operateLogs',
    ];
   // protected $defaultIncludes = ['detail','job','salary'];
    public function transform(User $user)
    {
        $array = [
            'id' => hashid_encode($user->id),
            'user_id' => $user->id,
            'phone' => $user->phone,
            'birth_time' => $user->birth_time,
            'name' => $user->name,
            'current_address' => $user->current_address,
            'status' => $user->status,
            'department' => $user->department,
            'position' => $user->position,
            'hire_shape' => $user->hire_shape,
            'entry_time' => $user->entry_time,
            'archive_time' => $user->entry_time,
            'position_type' => $user->position_type,


        ];


        if ($user->company) {
            $array['company'] = $user->company->name;
            $array['company_id'] = hashid_encode($user->company->id);
        }
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

}