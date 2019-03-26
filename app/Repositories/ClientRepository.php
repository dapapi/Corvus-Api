<?php

namespace App\Repositories;

use App\Models\Client;
use App\Models\Contact;
use App\User;
use Illuminate\Support\Facades\Cache;
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

    public function getPower(User $user,Client $client)
    {
        $cache_key = "power:user:".$user->id.":client:".$client->id;
        $power = Cache::get($cache_key);
        if ($power){
            return $power;
        }
        $power = [];
        $role_list = $user->roles()->pluck('id')->all();
        $repository = new ScopeRepository();
        $api_list = [
            'edit_client'    =>  ['uri'  =>  'clients/{id}','method'  =>  'put'],
            'add_contact'   =>  ['uri'  =>  'clients/{id}/contacts','method' =>  'post'],
            'edit_contact'  =>  ['uri'  =>  'clients/{id}/contacts/{id}','method'   =>  'put'],
            'del_contact'   =>  ['uri'  =>  'clients/{id}/contacts/{id}','method'   =>  'delete'],

        ];
        //登录用户对线索编辑权限验证
        foreach ($api_list as $key => $value){
            try{
                $repository->checkPower("clients/{id}",'put',$role_list,$client);
                $power[$key] = "true";
            }catch (\Exception $exception){
                $power[$key] = "false";
            }
        }
        Cache::put($cache_key,$power,1);
        return $power;

    }
}
