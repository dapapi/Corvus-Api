<?php

namespace App\Repositories;

use App\Models\Trail;
use App\User;

class TrailRepository
{
    public function getPower(User $user,Trail $trail)
    {
        $power = [];
        $role_list = $user->roles()->pluck('id')->all();
        $repository = new ScopeRepository();
        $api_list = [
            'edit_trail'    =>  ['uri'  =>  'trails/{id}','method'  =>  'put']
        ];

        //登录用户对线索编辑权限验证
        foreach ($api_list as $key => $value){
            try{
                $repository->checkPower($value['uri'],$value['method'],$role_list,$trail);
                $power[$key] = "true";
            }catch (Exception $exception){
                $power[$key] = "false";
            }
        }
        return $power;
    }
}
