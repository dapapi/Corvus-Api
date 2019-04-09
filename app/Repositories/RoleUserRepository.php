<?php

namespace App\Repositories;

use App\Models\RoleUser;

class RoleUserRepository
{
    public static function getRoleList($user_id)
    {
        return RoleUser::where('user_id', $user_id)->pluck('role_id');
    }
}
