<?php

namespace App\Repositories;

use App\Models\StarReport;
use function foo\func;
use Illuminate\Support\Facades\DB;

class StarReportRepository
{
    /**
     * 获取明星粉丝数据
     * @param $star_id
     * @param $star_time
     * @param $end_time
     */
    public static function getFensiByStarId($star_id,$starable_type,$start_time,$end_time){
        $result = StarReport::where('created_at','>=',"$start_time")
            ->where('created_at','<=',"$end_time")
            ->where('starable_id',$star_id)
            ->where('starable_type',$starable_type)
            ->groupBy(DB::raw(
                "date_format(created_at,'%Y-%m-%d'),platform_id"
            ))
            ->get([
                DB::raw("avg(count)"),
                'platform_id',
                DB::raw("date_format(created_at,'%Y-%m-%d') as date")
            ]);
        return $result;
    }
}
