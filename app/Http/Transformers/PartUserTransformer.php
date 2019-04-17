<?php

namespace App\Http\Transformers;

use App\Models\Department;
use App\User;
use League\Fractal\TransformerAbstract;
use Illuminate\Support\Facades\DB;
use App\ModuleUserType;
use App\ModuleableType;
use App\Models\Schedule;
use App\Models\DepartmentPrincipal;


class PartUserTransformer extends TransformerAbstract
{

   // protected $defaultIncludes = ['detail','job','salary'];
    public function transform(User $user)
    {
        //假头像
        $array = [
            'id' => hashid_encode($user->id),
            'name' => $user->name,
            'icon_url' => $user->icon_url,//'用户头像',
            'real_name'=>$user->real_name,
            'phone'=>$user->phone,
            'email'=>$user->email,
        ];

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
        $this_id = $user -> id;
//        $sch =  DB::select('select schedules.* from schedules inner join module_users on module_users.moduleable_id = schedules.id where module_users.user_id ='.$this_id.'
//        and ( (privacy = '.Schedule::OPEN.' and creator_id = '.$this_id.' and module_users.moduleable_type = '."'schedule'".' and module_users.type = 1) or (privacy= '.Schedule::SECRET.'
//        and module_users.moduleable_type = '."'schedule'".' and module_users.type = 1))  and schedules.start_at <=   '. "now()" .'and schedules.end_at >='. "now()" .'
//        and schedules.deleted_at is null order by start_at asc');
        $sch =  Schedule::select('schedules.*')
                 ->leftJoin('module_users as mu', function ($join) use ($this_id) {
                        $join->on('mu.moduleable_id', 'schedules.id');
                    })
                 ->where(function ($query) use ($user,$this_id) {
                     $query->where(function ($query) use ($user, $this_id) {
                         $query->where('privacy', Schedule::OPEN)
                             ->whereRaw("mu.moduleable_type='" . ModuleableType::SCHEDULE . "'")
                             ->whereRaw("mu.type='" . ModuleUserType::PARTICIPANT . "'")
                             ->Where('creator_id', $user->id)
                             ->whereRaw("mu.user_id='" . $this_id . "'");
                     })
                         ->orWhere(function ($query) use ($user) {
                             $query->where('privacy', Schedule::SECRET)
                                 ->whereRaw("mu.moduleable_type='" . ModuleableType::SCHEDULE . "'")
                                 ->whereRaw("mu.type='" . ModuleUserType::PARTICIPANT . "'");

                         });
                         })->whereRaw("schedules.start_at <='" . now() . "'")->whereRaw("schedules.end_at >='" . now() . "'")
            ->select('schedules.id','schedules.title','schedules.calendar_id','schedules.creator_id','schedules.is_allday','schedules.privacy'
                ,'schedules.start_at','schedules.end_at','schedules.position','schedules.repeat','schedules.desc')
            ->get();
//        $sql_with_bindings = str_replace_array('?', $sch->getBindings(), $sch->toSql());
//        dd($sql_with_bindings);
        return $this->collection($sch, new ScheduleTransformer());
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