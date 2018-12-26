<?php

namespace App\Http\Transformers;


use App\Models\Department;
use App\Models\RoleUser;
use App\User;
use League\Fractal\TransformerAbstract;
use Illuminate\Support\Facades\DB;


class RoleUserTransformer extends TransformerAbstract
{
    //protected $availableIncludes = ['users'];
    protected $defaultIncludes = ['users'];
    public function transform(RoleUser $roleUser)
    {
        $array = [
            'role_id' => hashid_encode($roleUser->role_id),
            'user_id' => hashid_encode($roleUser->user_id),

        ];
        return $array;
    }


    public function includeUsers(RoleUser $roleUser)
    {
        $userinfo = $roleUser->users;

        return $this->collection($userinfo, new UserTransformer());
    }


}