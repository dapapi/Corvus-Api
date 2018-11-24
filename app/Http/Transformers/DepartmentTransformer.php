<?php

namespace App\Http\Transformers;

use App\Models\Department;
use League\Fractal\TransformerAbstract;

class DepartmentTransformer extends TransformerAbstract
{
    protected $defaultIncludes = ['departments', 'users'];

    public function transform(Department $department)
    {
        $array = [
            'id' => hashid_encode($department->id),
            'name' => $department->name,
        ];

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