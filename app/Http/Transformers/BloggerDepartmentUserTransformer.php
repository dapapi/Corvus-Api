<?php

namespace App\Http\Transformers;

use App\Models\DepartmentUser;
use League\Fractal\TransformerAbstract;

class BloggerDepartmentUserTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['users'];
    public function transform(DepartmentUser $departmentUser)
    {
        return [
        ];

    }

    public function includeUsers(DepartmentUser $departmentUser)
    {
        $users = $departmentUser->bloggerusers;

        return $this->item($users, new UserTransformer());
    }
}