<?php

namespace App\Exports;
use Illuminate\Support\Facades\Auth;
use App\Models\Project;
use App\ModuleableType;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Qiniu\Http\Request;

class ProjectsExport implements FromQuery, WithMapping, WithHeadings
{

    use Exportable;
    public function __construct($request)
    {
        $this->request = $request;
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function query()
    {
        $request = $this->request;
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        $project_type = $request->get('project_type',null);
        $query =  Project::where(function ($query) use ($request, $payload,$user,$project_type) {
            if ($request->has('keyword'))
                $query->where('title', 'LIKE', '%' . $payload['keyword'] . '%');

            if ($request->has('principal_ids') && $payload['principal_ids']) {
                $payload['principal_ids'] = explode(',', $payload['principal_ids']);
                foreach ($payload['principal_ids'] as &$id) {
                    $id = hashid_decode((int)$id);
                }
                unset($id);
                $query->whereIn('principal_id', $payload['principal_ids']);
            }

            if ($request->has('project_type') && $project_type <> '3,4' ){
                $query->where('type',$project_type);

            }
            if($request->has('project_type') && $project_type == '3,4'){
                $query->whereIn('type',[$project_type]);
            }
            if ($request->has('status'))#项目状态
                $query->where('status', $payload['status']);
        });
        if ($request->has('my')){
            switch ($payload['my']){
                case 'my_principal'://我负责
                    $query->where('principal_id', $user->id);
                    break;
                case 'my_participant'://我参与
                    $query->leftJoin("module_users as mu2",function ($join){
                        $join->on("mu2.moduleable_id","projects.id")
                            ->where('mu2.moduleable_type',ModuleableType::PROJECT);
                    })->where('mu2.user_id',$user->id);
                    break;
                case 'my_create'://我创建
                    $query->where('projects.creator_id', $user->id);
                    break;

            }
        }
        $projects = $query->searchData()
            ->leftJoin('operate_logs',function($join){
                $join->on('projects.id','operate_logs.logable_id')
                    ->where('logable_type',ModuleableType::PROJECT)
                    ->where('operate_logs.method','4');
            })->groupBy('projects.id')
            ->orderBy('up_time', 'desc')->orderBy('projects.created_at', 'desc')->select(['projects.id','creator_id','project_number','trail_id','title','projects.type','privacy','projects.status',
                'principal_id','projected_expenditure','priority','start_at','end_at','projects.created_at','projects.updated_at', DB::raw("max(operate_logs.updated_at) as up_time"),'desc']);
//                $sql_with_bindings = str_replace_array('?', $projects->getBindings(), $projects->toSql());
////
//        dd($sql_with_bindings);
        return  $projects;
//        $projects = Project::query()->where(function ($query) use ($request, $payload,$userid) {
//            if ($request->has('keyword'))
//                $query->where('title', 'LIKE', '%' . $payload['keyword'] . '%');
//            if ($request->has('principal_ids') && $payload['principal_ids']) {
//                $payload['principal_ids'] = explode(',', $payload['principal_ids']);
//                foreach ($payload['principal_ids'] as &$id) {
//                    $id = hashid_decode((int)$id);
//                }
//                unset($id);
//                $query->whereIn('principal_id', $payload['principal_ids']);
//            }
//            if($request->has('administration'))
//                $query->where('principal_id','<>' ,$userid);
//            if($request->has('principal_id'))
//                $query->where('principal_id',$userid);
//
//            if ($request->has('type') && $payload['type'] <> '3,4'){
//                $query->where('type', $payload['type']);
//            }
//            if($request->has('type') && $payload['type'] == '3,4'){
//                $query->whereIn('type',[3,4]);
//            }
//            if ($request->has('status'))
//                $query->where('projects.status', $payload['status']);
////            if ($request->has('my')){
////                switch ($payload['my']){
////                    case 'my_principal'://我负责
////                        $query->where('principal_id', $userid);
////                        break;
////                    case 'my_participant'://我参与
////                        $query->leftJoin("module_users as mu2",function ($join){
////                            $join->on("mu2.moduleable_id","projects.id")
////                                ->where('mu2.moduleable_type',ModuleableType::PROJECT);
////                        })->where('mu2.user_id',$userid);
////                        break;
////                    case 'my_create'://我创建
////                        $query->where('projects.creator_id', $userid);
////                        break;
////
////                }
////            }
//
//        })->searchData()
//            ->leftJoin('operate_logs',function($join){
//                $join->on('projects.id','operate_logs.logable_id')
//                    ->where('logable_type',ModuleableType::PROJECT)
//                    ->where('operate_logs.method','2');
//            })->groupBy('projects.id')
//            ->orderBy('up_time', 'desc')->orderBy('projects.created_at', 'desc')->select(['projects.id','creator_id','project_number','trail_id','title','projects.type','privacy','projects.status',
//                'principal_id','projected_expenditure','priority','start_at','end_at','projects.created_at','projects.updated_at', DB::raw("max(operate_logs.updated_at) as up_time"),'desc']);
//         return  $projects;


    }

    /**
     * @param Blogger $blogger
     * @return array
     */
    public function map($project): array
    {

       $type = $this->type($project->type);
       $trail = $project->trail()->first();
       if($trail){
           $trail_title = $trail->title;
           $resource_type  = $this->expectation($trail->resource_type);
           $expectations  = $trail->bloggerExpectations()->get(['nickname']);
                   if (count($expectations) <= 0) {
                       $expectations = $trail->expectations->toArray();
                       foreach ($expectations as $key => $val){
                           if($val['name']){
                               $expectations_name = $val['name'];
                           }else{
                               $expectations_name = null;
                           }
                       }
                   }else{
                       $expectations = $expectations->toArray();
                      foreach ($expectations as $key => $val){
                          if($val['nickname']){
                              $expectations_name = $val['nickname'];
                          }else{
                              $expectations_name = null;
                          }
                      }
                   }
           $fee = $trail->fee;
       }else{
           $trail_title = null;
           $resource_type = null;
           $expectations_name = null;
           $fee = null;
       }
        $title = $project->title;
       $principal = $project->principal()->first();
       if($principal){
           $principal_name = $principal->name;
       }else{
           $principal_name = null;
       }
       $priority = $this->status($project->priority);
       $start_at = $project->start_at;
       $end_at =  $project->end_at;
        return [
            $type,
            $trail_title,
            $title,
            $principal_name,
            $resource_type,
            $expectations_name,
            $fee,
            $priority,
            $start_at,
            $end_at
            ];
    }

    public function headings(): array
    {
        return [
            '项目类型',
            '销售线索名称',
            '项目名称',
            '项目负责人',
            '项目来源',
            '目标艺人',
            '预计订单收入',
            '优先级',
            '项目开始时间',
            '项目结束时间'



        ];
    }
    /**
     * @param string $type
     * @return string $type
     */
    private function expectation($expectations)
    {
        switch ($expectations) {
            case 1:
                $expectations = '商务邮箱';
                break;
            case 2:
                $expectations = '工作室邮箱';
                break;
            case 3:
                $expectations = '微信公众号';
                break;
            case 4:
                $expectations = '员工';
                break;
            case 5:
                $expectations = '公司高管';
                break;
            case 6:
                $expectations = '纯中介';
                break;
            case 7:
                $expectations = '香港中介';
                break;
            case 8:
                $expectations = '台湾中介';
                break;
            case 9:
                $expectations = '复购直客';
                break;
            case 10:
                $expectations = '媒体介绍';
                break;
            case 11:
                $expectations = '公关or广告公司';
                break;

        }
        return $expectations;
    }
    /**
     * @param string $type
     * @return string $type
     */
    private function status($status)
    {
        switch ($status) {
            case 1:
                $status = 'S';
                break;
            case 2:
                $status = 'A';
                break;
            case 3:
                $status = 'B';
                break;
            case 4:
                $status = 'C';
                break;
        }
        return $status;
    }
    /**
     * @param string $type
     * @return string $type
     */
    private function type($type)
    {
        switch ($type) {
            case 1:
                $type = '影视项目';
                break;
            case 2:
                $type = '综艺项目';
                break;
            case 3:
                $type = '商务代言';
                break;
            case 4:
                $type = 'papi项目';
                break;
            case 5:
                $type = '基础项目';
                break;
        }
        return $type;
    }
}
