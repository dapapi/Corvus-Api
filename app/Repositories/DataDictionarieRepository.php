<?php

namespace App\Repositories;

use App\Models\DataDictionarie;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DataDictionarieRepository
{
    /**
     * 根据父id获取code和val
     * @param $parent_id
     * @return code:val
     */
    public static function getCodeAndVal($parent_id)
    {
        $key = "data_directionarie:parent_id:{$parent_id}:codeandval";
        $res = Cache::get($key);
        if(!$res){
            $res = DataDictionarie::where('parent_id',$parent_id)->select(DB::raw("concat('code',':','val')"))->get();
            Cache::put($key,$res,Carbon::now()->addMinutes(1));
        }
    }
}
