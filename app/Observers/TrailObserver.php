<?php

namespace App\Observers;

use App\Models\Trail;
use Illuminate\Support\Facades\Auth;

class TrailObserver
{

    public function saved(Trail $trail)
    {
        if (!$trail->getDirty()) return false;
        $haid = hashid_encode($trail->id);   //haid
        $keys  = "trails_$haid";
       $tab =  app('redis')->connection('trails')->hgetall($keys);  //$key  对应数据
       if(is_array($tab))  return false;
         foreach($trail->getDirty() as $key => $val){
             if(array_key_exists($val,$tab));
             $tab[$key]  = $val;
         }
        app('redis')->connection('trails')->hmset($keys, $tab);
    }
    public function deleted(Trail $trail)
    {
        $haid[] = hashid_encode($trail->id);   //haid
        if(!is_array($haid)) return false;
        $user = Auth::guard('api')->user();
        foreach($haid as $value){
            $hashName = 'trails_'.$value;
            app('redis')->connection('trails')->del($hashName);
            app('redis')->connection('trails')->zrem( 'trails_sort_'.$user->id,$value);
        }
//        //   要检索内存的数据
//        $value = "$blacklist->content";
//
//
//        $key = "$blacklist->type";
//        // 检索 Redis 中是否存在数据
//
//        app('redis')->connection('content')->hdel($key, $value);
    }
    public function updated(Trail $trail)
    {
      //  dd(4);
//        if ($trail->getDirty()) {
//            // ..... 这里是标题被修改了的逻辑
//        }
//        return $trail->getDirty();
    }
    public function created(Trail $trail)   //t + 1 时刻
    {
        $user = Auth::guard('api')->user();
        app('redis')->connection('trails')->del('trails_sort_'.$user->id);
//        $data = $trail->toArray();
//        if(!is_numeric(hashid_encode($data['id'])) || !is_array($data)) return false;
//        $hashName = $hash_prefix.'_'.$data['id'];
//        $user = Auth::guard('api')->user();
//        $this->_redis->hmset($hashName, $data);
//        $this->_redis->zadd($hash_prefix.'_sort_'.$user->id,$id,$data['id']);  //  zadd  表名    score   value
//        dd(3);
//        if ($trail->getDirty()) {
//            // ..... 这里是标题被修改了的逻辑
//        }
//        return $trail->getDirty();
    }
}