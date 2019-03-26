<?php

namespace App\Repositories;

use App\Models\Blogger;
use App\User;
use Illuminate\Support\Facades\Cache;

class BloggerRepository
{
    public function getPower(User $user,Blogger $blogger)
    {
        $cache_key = "power:user:".$user->id.":blogger:".$blogger->id;
        $power = Cache::get($cache_key);
        if ($power){
            return $power;
        }
        $power = [];
        $role_list = $user->roles()->pluck('id')->all();
        $repository = new ScopeRepository();
        $api_list = [
            'edit_blogger'  =>  ['uri'  =>  'bloggers/{id}' , 'method'  =>  'put'],
            'edit_produser'  =>  ['uri'  =>  '/bloggers/{id}/produser','method'  =>  'post'],

        ];
        //登录用户对博主编辑权限验证
        foreach ($api_list as $key => $value){
            try{
                $repository->checkPower($value['uri'],$value['method'],$role_list,$blogger);
                $power[$key] = "true";
            }catch (\Exception $exception){
                $power[$key] = "false";
            }
        }
        Cache::put($cache_key,$power,1);
        return $power;
    }
}
