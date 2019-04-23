<?php

namespace App\Http\Transformers;

<<<<<<< HEAD
=======

use App\Http\Transformers\User\UserSimpleTransformer;
>>>>>>> develop
use App\Models\Department;
use App\Models\Role;
use App\User;
use League\Fractal\TransformerAbstract;
use Illuminate\Support\Facades\DB;


class RoleTransformer extends TransformerAbstract
{
    protected $availableIncludes = [

    ];
    protected $defaultIncludes = ['users'];
    public function transform(Role $role)
    {
        $array = [
            'id' => hashid_encode($role->id),
            'group_id' => hashid_encode($role->group_id),
            'name' => $role->name,
            'description' => $role->description,
        ];
        return $array;
    }


    public function includeUsers(Role $role)
    {
        $userinfo = $role->users;

<<<<<<< HEAD
        return $this->collection($userinfo, new PartUserTransformer());
=======
        return $this->collection($userinfo, new UserSimpleTransformer());

>>>>>>> develop
    }


}
