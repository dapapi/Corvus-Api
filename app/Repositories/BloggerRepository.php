<?php

namespace App\Repositories;

use App\Models\Blogger;
use App\ModuleableType;
use App\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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

    /**
     * 博主列表
     * @author lile
     * @date 2019-04-02
     */
    public static function getBloggerList($condition)
    {
        if ($condition == null){
            $condition['where'] = null;
            $condition['placeholder'] = [];
        }
        $where = Blogger::powerConditionSql();
        $placeholder = $condition['placeholder'];
        $sql = <<<AAA
            select 
              bloggers.nickname,bloggers.id,bloggers.sign_contract_status,bloggers.weibo_fans_num,bloggers.type_id,bloggers.sign_contract_at,bloggers.terminate_agreement_at,bloggers.created_at,bloggers.last_follow_up_at,bloggers.communication_status,
              bloggers.publicity->'$[*].user_name' as publicity_name
--               group_concat(users.name) as publicity 
            from bloggers 
--             left join module_users on module_users.moduleable_id = bloggers.id 
--                       and module_users.moduleable_type = :moduleable_type 
--             left join department_user on department_user.user_id = module_users.user_id
--             left join users on users.id = module_users.user_id
            where (1 = 1 {$where})  {$condition['where']}
--             group by bloggers.id
            limit 0,10
AAA;
//        dd($sql);
        $placeholder = $condition['placeholder'];
        $placeholder[":moduleable_type"] = ModuleableType::BLOGGER;
//        DB::connection()->enableQueryLog();
        return DB::select($sql,$placeholder);
//        dd(DB::getQueryLog());

    }
}
