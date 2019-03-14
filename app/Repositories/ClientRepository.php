<?php

namespace App\Repositories;

use App\Models\Client;

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
        return Client::leftJoin('contacts','contacts.client_id', '=', 'clients.id')
            ->leftJoin('operate_logs',function ($join){
                $join->on('operate_logs.logable_id', '=', 'clients.id')
                    ->whereRaw('logable_type = "client"');
            })->leftJoin('department_user','department_user.user_id','clients.principal_id');
    }
}
