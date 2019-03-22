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
        $sysCode = config('app.nc_syscode');//系统名称
        $sysPass = config('app.nc_syspass');//系统密码
        $companyId = config('app.nc_companyid');//目标系统
        $login_url = config('app.nc_login');//登录地址
        $options = compact($sysCode,$sysPass,$companyId);
        $response = $client->request('post',$login_url,$options);
        if($response->getStatusCode() == 200){
            $body = json_decode($response->getBody());
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
