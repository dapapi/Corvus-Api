<?php

namespace App\Repositories;

use App\Models\OperateLog;
use App\Models\Project;
use App\Models\Star;
use App\Models\Trail;
use App\Models\TrailStar;
use App\ModuleableType;
use App\OperateLogMethod;
use Illuminate\Support\Facades\DB;

class ProjectRepository
{
    public static function getProjectBySatrId($star_id)
    {
        $result = (new Star())->setTable("s")->from("stars as s")
            ->select(DB::raw('distinct p.*'))
            ->leftJoin('contracts as c',function ($join)use ($star_id){
                $join->on('c.stars','s.id')
                    ->where('star_type',ModuleableType::STAR);
            })->leftJoin('approval_form_business as afb',function ($join){
                $join->on('afb.form_instance_number','c.form_instance_number')
                    ->where('afb.form_status',302);//审批完成
            })
            ->leftJoin('projects as p',function ($join){
                $join->on('p.id','c.project_id');
            })
            ->leftJoin('operate_logs as ol',function ($join){
                $join->on('ol.logable_id','p.id')
                    ->where('ol.logable_type',ModuleableType::STAR)

                    ->where('ol.method',OperateLogMethod::FOLLOW_UP);
            })
            ->where('stars',$star_id)
            ->orderBy('ol.created_at','desc')->limit(3)
            ->get();
        return $result;
    }
}
