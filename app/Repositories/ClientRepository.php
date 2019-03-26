<?php

namespace App\Repositories;

use App\Models\Client;
use Illuminate\Support\Facades\DB;

class ClientRepository
{
    /**
     * 返回自定义筛选基本链表语句
     * @return mixed
     * @author lile
     * @date 2019-03-12 17:48
     */
    public function clientCustomSiftBuilder()
    {
        $sub_sql = DB::table("operate_logs as ol")->select(['ol.id','ol.user_id','ol.method','logable_id',DB::raw('max(ol.created_at) as created_at')])
            ->where('ol.logable_type','client')

            ->groupBy('ol.logable_id','ol.method');
        return Client::leftJoin('contacts','contacts.client_id', '=', 'clients.id')
            ->leftJoin(DB::raw("({$sub_sql->toSql()}) as operate_logs"),'operate_logs.logable_id',"clients.id")
            ->leftJoin('department_user','department_user.user_id','clients.principal_id')
            ->mergeBindings($sub_sql);
    }
}
