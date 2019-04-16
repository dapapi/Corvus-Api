<?php

namespace App\Observers;

use App\Models\GroupList;

class DataDictionarieObserver
{

    public function saved(GroupList $groupList)
    {

dd(2);
//        //   要写入内存的数据
//        $value = array_merge([
//            "$blacklist->content"=>$blacklist->type
//        ]);
//
//        // 要写入内存的数据的key
//        // $key = "blacklist:id:{$blacklist->id}";
//        $key = "$blacklist->type";
//        // 更新 Redis
//        app('redis')->connection('content')->hmset($key, $value);
    }
    public function deleted(GroupList $groupList)
    {
        dd(3);
//        //   要检索内存的数据
//        $value = "$blacklist->content";
//
//
//        $key = "$blacklist->type";
//        // 检索 Redis 中是否存在数据
//
//        app('redis')->connection('content')->hdel($key, $value);
    }
}