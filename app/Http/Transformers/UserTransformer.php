<?php

namespace App\Http\Transformers;

use App\Models\Department;
use App\User;
use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{
    protected $availableIncludes = [
        'department',
    ];
    public function transform(User $user)
    {
        $department = $user->department()->first();
        if (!$department)
            return [
                'id' => hashid_encode($user->id),
                'name' => $user->name,
            ];
        
        $company = $this->department($department);
        return [
            'id' => hashid_encode($user->id),
            'name' => $user->name,
            'company' => $company->name,
            'company_id' => hashid_encode($company->id),
        ];
    }

    public function includeDepartment(User $user)
    {
        $department = $user->department()->first();
        if (!$department)
            return null;

        return $this->item($department, new DepartmentTransformer());
    }

    private function department(Department $department)
    {
        $department = $department->pDepartment;
        if ($department->department_pid == 0) {
            return $department;
        } else {
            $this->department($department);
        }
    }
}