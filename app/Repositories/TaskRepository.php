<?php

namespace App\Repositories;

use App\Models\Task;
use App\User;
use Illuminate\Support\Facades\Cache;

class TaskRepository
{
    public function getPower(User $user,Task $task)
    {
        $cache_key = "power:user:".$user->id.":task:".$task->id;
        $power = Cache::get($cache_key);


        if ($power){
            return $power;
        }

        $power = [];
        $role_list = $user->roles()->pluck('id')->all();
        $repository = new ScopeRepository();
        $api_list = [
            "edit_task" =>  ['uri'   =>  'tasks/{id}','method'   =>  'put'],//编辑任务
            'del_task'  =>  ['uri'  =>  'tasks/{id}','method'   =>  'delete'],//删除任务
            'add_subtask'   =>  ['uri'  =>'tasks/{task}/subtask','method'   =>  'post'],//添加子任务
        ];

        //登录用户对线索编辑权限验证
        foreach ($api_list as $key => $value){
            try{
                $repository->checkPower($value['uri'],$value['method'],$role_list,$task);
                $power[$key] = "true";
            }catch (\Exception $exception){
                $power[$key] = "false";
            }
        }
        Cache::put($cache_key,$power,1);
        return $power;
    }
}
