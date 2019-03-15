<?php

namespace App\Repositories;

use App\Models\OperateLog;
use App\Models\Star;
use App\OperateLogMethod;
use Illuminate\Support\Facades\DB;

class StarRepository
{
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
}
