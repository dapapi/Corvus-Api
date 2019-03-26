<?php

namespace App\Repositories;


use App\Annotation\DescAnnotation;
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
    public static function getSignContractProjectBySatr($id,$name,$star_type,$pageSize)
    {

        //绑定权限参数
        $builder = Project::searchData();
        $bingings = $builder->getBindings();
        $sql = str_replace("?","%s",$builder->toSql());
        $sql = sprintf($sql,...$bingings);
        $query = Project::from(DB::raw("({$sql}) as projects"))->
        leftJoin("contracts as c",'projects.id',"c.project_id")
            ->leftJoin("approval_flow_execute as afe",function ($join){
                $join->on("afe.form_instance_number","c.form_instance_number");
            })
            ->leftJoin('users','users.id','projects.principal_id')
            ->leftJoin('project_bills_resources as pbr','projects.id',"pbr.resourceable_id")
            ->leftJoin("project_bills_resources_users as pbru",function ($join){
                $join->on( 'pbr.id',"pbru.moduleable_id");
            })
          //  ->where('afe.flow_type_id',232)
            ->whereRaw("find_in_set({$id},c.stars)")
            ->where("c.star_type",$star_type)
           // ->where("pbru.moduleable_title",$name);
//        $sql_with_bindings = str_replace_array('?', $query->getBindings(), $query->toSql());
//                dd($sql_with_bindings);
           ->select("projects.id","projects.title","projects.created_at","projects.principal_id","pbru.money as contract_sharing_ratio" ,"users.icon_url");
        return $query->paginate($pageSize);
    }
}
