<?php

namespace App\Repositories;

use App\Models\TrailStar;
use App\ModuleableType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use mysql_xdevapi\Exception;

class TrailStarRepository
{
    /**
     * 批量为销售线索增加艺人
     * @param $trail 线索
     * @param $star_list 艺人和博主列表 [['id'=>1234],['flag'=>'star|blogger']]
     * @param $type 推荐艺人或者目标艺人
     * 存储线索关联艺人
     */
    public function store($trail,$star_list,$type)
    {
        $trail_star_list = [];
        foreach ($star_list as $key => $star){
            if ($star['flag'] == ModuleableType::BLOGGER)
                $starable_type = ModuleableType::BLOGGER;
            elseif($star['flag'] == ModuleableType::STAR)
                $starable_type = ModuleableType::STAR;
            $trail_star_list[] = [
                'starable_id'=>hashid_decode($star['id']),
                'starable_type'=>$starable_type,
                'type'=>$type,
                'trail_id'=>$trail->id,
                'created_at'=>Carbon::now(),
                'updated_at'=>Carbon::now(),
            ];

        }
        DB::table("trail_star")->insert($trail_star_list);
    }

    /**
     * 删除销售线索对应的艺人
     * @param $trail_id 线索id
     * @param $type 推荐艺人或者目标艺人
     */
    public function deleteTrailStar($trail_id,$type){
      TrailStar::where('trail_id',$trail_id)->where('type',$type)->delete();
    }

    /**
     * 获取线索关联的博主和艺人列表
     *
     * @param $trail_id 线索id
     * @param $type 目标艺人还是推荐艺人
     */
    public function getStarListByTrailId($trail_id,$type)
    {
       $first = TrailStar::select('stars.name')
           ->join('stars',function ($join)use ($type){
               $join->on('stars.id','trail_star.starable_id')
                   ->whereRaw("trail_star.starable_type = '".ModuleableType::STAR."'");
           })
           ->where('trail_star.trail_id',$trail_id)->where('trail_star.type',$type);

       $res = TrailStar::select('nickname')->join('bloggers',function ($join)use ($type) {
           $join->on('trail_star.starable_id','=','bloggers.id')
               ->where("trail_star.starable_type",'blogger');
       })->where('trail_star.trail_id',$trail_id)->where('trail_star.type',1)
           ->union($first)
           ->get()->toArray();


       return implode(",",array_column($res,'nickname'));
    }
}
