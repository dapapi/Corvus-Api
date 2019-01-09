<?php

namespace App\Http\Transformers;

use App\Models\Department;
use League\Fractal\TransformerAbstract;
use Illuminate\Support\Facades\DB;

class DepartmentTransformer extends TransformerAbstract
{
    protected $defaultIncludes = ['departments', 'users'];

    public function transform(Department $department)
    {
        $array = [
            'id' => hashid_encode($department->id),
            'name' => $department->name,
            'department_pid' => hashid_encode($department->department_pid),
        ];

        $res = DB::table('department_principal as dp')
            ->join('users', function ($join) {
                $join->on('dp.user_id','=', 'users.id');
            })->select('users.name','users.id')
            ->where('dp.department_id', $department->id)->get()->toArray();

        if (!empty($res)) {
            $array['is_department_principal'] = 1;
            $array['is_department_username'] = $res[0]->name;
            $array['is_department_user_id'] = $res[0]->id;

        }else{
            $array['is_department_principal'] = 0;
            //$array['is_department_username'] = $res[0]['name'];

        }

        return $array;
    }

    public function includeDepartments(Department $department)
    {
        $departments = $department->departments;
        return $this->collection($departments, new DepartmentTransformer());
    }

    public function includeUsers(Department $department)
    {
        $users = $department->users;

        return $this->collection($users, new UserTransformer());
    }
}