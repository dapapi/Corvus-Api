<?php

namespace App\Repositories;

use App\Models\Star;
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
        return Star::leftJoin('module_users',function ($join){
            $join->on('module_users.moduleable_id', '=' ,'stars.id')
                ->whereRaw('moduleable_type = "star"');
        })->leftJoin('department_user','department_user.user_id','module_users.user_id')
            ->leftJoin('operate_logs',function ($join){
                $join->on('logable_id','stars.id')
                    ->whereRaw('operate_logs.logable_type = "star"');
            })
            ->leftJoin('contracts',function ($join){
                $join->whereRaw('find_in_set(stars.id,stars)');
            });
    }
}
