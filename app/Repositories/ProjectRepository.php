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
use App\User;
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


    public function getPower(User $user,Project $project)
    {
        $power = [];
        $role_list = $user->roles()->pluck('id')->all();
        $repository = new ScopeRepository();
        //需要验证权限的功能
        $api_list = [
            "edit_project"  =>  ['method'=>'put','uri' =>  'projects/{id}'],//便捷项目
            "add_bill"  =>  ['method'=>'post','uri' =>  '/projects/{id}/bill'],//新建结算单
            "edit_bill"  =>  ['method'=>'put','uri' =>  '/projects/{id}/bill'],//编辑结算单
            'add_returned_money'    =>  ['method'   =>  'post','url'    =>  '/projects/{id}/returned/money'],//新建回款期次
            'edit_returned_money'    =>  ['method'   =>  'put','url'    =>  '/projects/{id}/returned/money'],//编辑回款期次
//            'add_'   =>  ['method'   =>  'post','uri'   =>  'projects/{id}/returned/{id}/money'],//新建回款记录
//            'edit_' =>  ['method'   =>  'put'  ,'uri'   =>  'returned/money/{projectreturnedmoney}'],//修改回款记录
//            'delete_'   =>  ['method'   =>  'delete','uri'  =>'returned/money/{projectreturnedmoney}'],//删除回款记录
        ];
        //验证权限
        foreach($api_list as $key => $value){
            try{
                //获取用户角色
                $repository->checkPower($value['uri'],$value['method'],$role_list,$project);
                $power[$key] = "true";
            }catch (\Exception $exception){
                $power[$key] = "false";
            }
        }
        return $power;
    }
}
