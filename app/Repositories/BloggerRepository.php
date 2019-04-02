<?php

namespace App\Repositories;

use App\Models\Blogger;
use App\Models\Star;
use App\ModuleableType;
use App\ModuleUserType;
use App\SignContractStatus;
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
    public static function getBloggerList($condition,$search_field,$pageSize,$page)
    {
        if ($condition == null){
            $condition['where'] = null;
            $condition['placeholder'] = [];
        }

//        $placeholder = $condition['placeholder'];
        $where = Blogger::powerConditionSql();

        $offset = ($page-1) * $pageSize;


        //第一次进入时的sql

//        if (in_array('module_users.user_id',$search_field) ||in_array('department_user.department_id',$search_field) ) {//根据经理人，部门查询的sql
            $sql = <<<AAA
            select
                bloggers.nickname,bloggers.id,blogger_types.name as type,bloggers.sign_contract_status,bloggers.weibo_fans_num,bloggers.type_id,bloggers.sign_contract_at,bloggers.terminate_agreement_at,bloggers.created_at,bloggers.last_follow_up_at,bloggers.communication_status,group_concat(users.name) as publicity_user_names
            from bloggers
            left join module_users on module_users.moduleable_id = bloggers.id and module_users.moduleable_type = :moduleable_type and module_users.type = :module_users_type
            left join department_user on department_user.user_id = module_users.user_id
            left join users on department_user.user_id = users.id
            left join blogger_types on blogger_types.id = bloggers.type_id

                group by bloggers.id
AAA;

            $placeholder[":moduleable_type"] = ModuleableType::BLOGGER;
            $placeholder[":module_users_type"] = ModuleUserType::PUBLICITY;
//        }else{
//            $sql = <<<AAA
//            select
//              bloggers.nickname,bloggers.id,blogger_types.name as type,bloggers.sign_contract_status,bloggers.weibo_fans_num,bloggers.type_id,bloggers.sign_contract_at,bloggers.terminate_agreement_at,bloggers.created_at,bloggers.last_follow_up_at,bloggers.communication_status
//            from bloggers
//            left join blogger_types on blogger_types.id = bloggers.type_id
//            where (1 = 1 {$where})  {$condition['where']}
//AAA;

//        }
//        $count = DB::select("select count(*) from ({$sql}) as temp");

        $sql .= " limit {$offset},{$pageSize}";
        $data = DB::select($sql,$placeholder);
//        $meta = [
//            "pagination"=> [
//                "total"=> $count,
//                "count"=> $count($data),
//                "per_page"=> $page - 1 == 0 ? 1: $page-1,
//                "current_page"=> $page,
//                "total_pages"=> ($count/$pageSize) + 1,
//                "links"=> [
//                    "next"=> "http://corvus.cn/stars/filter?page=2"
//                ],
//            ]
//        ];
        return [
            $data,
            [],
        ];
    }

    public static function getBloggerList2($search_field)
    {
        if (in_array('module_users.user_id',$search_field) ||in_array('department_user.department_id',$search_field) ) {//根据经理人，部门查询的sql
            return Blogger::select('bloggers.nickname','bloggers.id',DB::raw('blogger_types.name as type'),'bloggers.sign_contract_status','bloggers.weibo_fans_num','bloggers.type_id','bloggers.sign_contract_at','bloggers.terminate_agreement_at','bloggers.created_at','bloggers.last_follow_up_at','bloggers.communication_status',DB::raw('group_concat(users.name) as publicity_user_names'))
                ->leftJoin('blogger_types','blogger_types.id','bloggers.type_id')
                ->leftJoin('module_users',function ($join){
                    $join->on('bloggers.id','module_users.moduleable_id');
//                        ->where('module_users.moduleable_type',ModuleUserType::PUBLICITY);
                })
                ->leftJoin('users','users.id','module_users.user_id')
                ->groupBy('bloggers.id');

        }else{

            return Blogger::select('bloggers.nickname', 'bloggers.id', DB::raw('blogger_types.name as type'), 'bloggers.sign_contract_status', 'bloggers.weibo_fans_num', 'bloggers.type_id', 'bloggers.sign_contract_at', 'bloggers.terminate_agreement_at', 'bloggers.created_at', 'bloggers.last_follow_up_at', 'bloggers.communication_status', DB::raw('publicity_user_names'))
                ->leftJoin('blogger_types', 'blogger_types.id', 'bloggers.type_id');
        }
    }
}
