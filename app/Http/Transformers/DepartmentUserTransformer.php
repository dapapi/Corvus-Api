<?php

namespace App\Http\Transformers;

use App\Models\DepartmentUser;
use League\Fractal\TransformerAbstract;

class DepartmentUserTransformer extends TransformerAbstract
{

    public function transform(DepartmentUser $departmentUser)
    {
        $array = [
            'id' => hashid_encode($departmentUser->id),
            'name' => $departmentUser->name,
        ];

        return $array;
    }

    public function includeDepartments(Department $department)
    {
        $departments = $department->departments;

        return $this->collection($departments, new DepartmentTransformer());
    }

    public function includeUsers(DepartmentUser $departmentUser)
    {
        $users = $departmentUser->users;

        return $this->collection($users, new UserTransformer());
    }
}