<?php

namespace App\Repositories;

use App\Models\RoleUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class RoleUserRepository
{
    //获取当前用户角色列表
    public static function getRoleList($user_id)
    {
        $key = "user:{$user_id}:role";
        $role_list = Cache::get($key);
        if (!$role_list){
            $role_list = RoleUser::where('user_id', $user_id)->pluck('role_id');
            Cache::put($key,$role_list,Carbon::now()->addMinutes(1));
        }
        return $role_list;

    }
}
