<?php

namespace App\Repositories;

use App\Models\OperateLog;
use App\Models\Star;
use App\ModuleableType;
use App\OperateLogMethod;
use App\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StarRepository
{

    protected $star;

    public function __construct(Star $star)
    {
        $this->star = $star;
    }

    /**
     * 返回自定义筛选的基础链表语句
     * @return mixed
     * @author lile
     * @date 2019-03-12 17:21
     */
    public function starCustomSiftBuilder()
    {
        $sub_sql = DB::table("operate_logs as ol")->select(['ol.id','ol.user_id','ol.method','logable_id',DB::raw('max(ol.created_at) as created_at')])
            ->where('ol.logable_type','star')

            ->groupBy('ol.logable_id','ol.method','ol.logable_type');

//        $sub_sql = DB::table("operate_logs as ol2")
//            ->select('ol2.id','ol2.created_at','ol2.logable_id','ol2.user_id','ol2.method')
//            ->join(DB::raw("({$inner_sql->toSql()}) as ff"),'ff.id','ol2.id')->mergeBindings($inner_sql);

        return Star::leftJoin('module_users',function ($join){
            $join->on('module_users.moduleable_id', '=' ,'stars.id')
                ->whereRaw('moduleable_type = "star"');
        })->leftJoin('department_user','department_user.user_id','module_users.user_id')
            ->leftJoin(DB::raw("({$sub_sql->toSql()}) as operate_logs"),'operate_logs.logable_id',"stars.id")
            ->leftJoin('contracts',function ($join){
                $join->whereRaw('find_in_set(stars.id,stars)');
            })->mergeBindings($sub_sql);
    }

    public function getPower(User $user,Star $star)
    {
        $cache_key = "power:user:".$user->id.":star:".$star->id;
        $power = Cache::get($cache_key);
        if ($power){
            return $power;
        }
        $power = [];
        $role_list = $user->roles()->pluck('id')->all();
        $repository = new ScopeRepository();
        //登录用户对艺人编辑权限验证
        $api_list = [
            'edit_star' =>  ['uri'  =>  'stars/{id}','method'   =>  'put'],
            'add_work'  =>  ['uri'  =>  '/stars/{id}/works','method'    =>  'post'],
            "edit_publicity"  =>  ['uri'  =>  'stars/{id}/publicity','method' =>  'post'],
            "edit_broker"  =>  ['uri'  =>  '/stars/{id}/broker','method'   =>  'post'],

        ];
        foreach ($api_list as $key => $value){
            try{
                $repository->checkPower("stars/{id}",'put',$role_list,$star);
                $power[$key] = "true";
            }catch (\Exception $exception){
                $power[$key] =  "false";
            }
        }
        Cache::put($cache_key,$power,1);
        return $power;
    }
    public static function getStarList()
    {
        return Star::select("stars.id","stars.name",'stars.avatar',"stars.weibo_fans_num","stars.source","stars.created_at","stars.last_follow_up_at")
            ->searchData()
            ->leftJoin('module_users',function ($join){
                $join->on('module_users.moduleable_id','stars.id')
                    ->where('module_users.moduleable_type',"''".ModuleableType::STAR."''");
            })
            ->leftJoin('department_user','department_user.user_id','module_users.user_id');
//        $sql = <<<AAA
//        select stars.id,stars.name,stars.weibo_fans_num,stars.source,stars.created_at,stars.last_follow_up_at from stars
//          left join module_users on module_users.moduleable_id = stars.id and module_users.moduleable_type = :moduleable_type
//          left join department_user on department_user.user_id = module_users.user_id
//--            where stars.id = :star_id
//AAA;
//        $placeholder = $where['placeholder'];
//        $placeholder[":moduleable_type"] = ModuleableType::STAR;
//        return DB::select($sql,[":moduleable_type" => ModuleableType::STAR]);
//        return DB::select($sql,$placeholder);
    }
}
