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
    ];
    public function transform(User $user)
    {
        $array = [
            'id' => hashid_encode($user->id),
            'user_id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'status' => $user->status,
            'department' => $user->department,
            'position' => $user->position,
            'hire_shape' => $user->hire_shape,
            'entry_time' => $user->entry_time,

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