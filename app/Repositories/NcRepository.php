<?php

namespace App\Repositories;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

/**
 * Class NcRepository
 * @package App\Repositories
 * @author lile
 * @date 2019-03-22 15:53
 */
class NcRepository
{
    public function getToken()
    {
        $cache_key = "nc:token";
        $token = Cache::get($cache_key);
        if ($token){
            return $token;
        }
        $client = new Client();
        $sysCode = config('nc.nc_syscode');//系统名称
        $sysPass = config('nc.nc_syspass');//系统密码
        $companyId = config('nc.nc_companyid');//目标系统
        $login_url = config('nc.nc_login');//登录地址
        $options = [
            "json"  =>  ["sysCode"   =>  $sysCode,"sysPass"  =>  $sysPass,"companyId"    =>$companyId],
//            "headers"   =>  ['Accept'   =>  'application/json']
        ];

        $response = $client->request('POST',$login_url,$options);
        if($response->getStatusCode() == 200){
            $body = json_decode($response->getBody(),true);
            if ($body['success'] == "true"){
                $token = $body['data']['token'];
                Cache::put($cache_key,$token,50);
                return $token;
            }
            return false;
        }
        return false;
    }
}
