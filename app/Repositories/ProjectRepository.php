<?php

namespace App\Repositories;


use App\Models\ApprovalFlowExecute;
use App\Models\Contract;
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

    /**
     * 获取艺人签约合同
     */
    public static function getSignContractProjectBySatr($id,$star_type,$pageSize)
    {
//        //查询艺人下的项目
//        $contracts = Contract::whereRaw("find_in_set({$star_id},stars)")
//            ->where("star_type","stars")
//            ->whereRaw("project_id is not null")
//            ->select('id',"project_id","form_instance_number")->get()->toArray();
//        $instance_numbers = array_column($contracts,"form_instance_number");
//        //获取项目中审核通过的项目
//        $flow_exceute = ApprovalFlowExecute::where("flow_type_id",232)->whereIn('form_instance_number',$instance_numbers)
//            ->select("form_instance_number")->get()->toArray();
//        $pass_instance_numbers = array_column($flow_exceute,"form_instance_number");
//        //获取审核通过的项目id
//        $contracts = array_column($contracts,null,'form_instance_number');
//        $project_ids = [];
//        foreach ($contracts as $key => $value){
//            if (in_array($key,$pass_instance_numbers)){
//                $project_ids[] = $value['project_id'];
//            }
//        }
//        //查找项目
//        return Project::whereIn('id',$project_ids)->paginate($pageSize);
        $query = (new Project)->setTable("p")->from("projects as p")
            ->leftJoin("contracts as c",'p.id',"c.project_id")
            ->leftJoin("approval_flow_execute as afe",function ($join){
                $join->on("afe.form_instance_number","c.form_instance_number");
            })
            ->where('afe.flow_type_id',232)->whereRaw("find_in_set({$id},c.stars)")
            ->where("star_type",$star_type)
            ->select("p.id","p.title","p.created_at");
        return $query->paginate($pageSize);


    }
}
