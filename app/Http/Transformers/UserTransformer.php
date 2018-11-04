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
        $array = [
            'id' => hashid_encode($user->id),
            'name' => $user->name,
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
}