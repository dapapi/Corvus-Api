<?php

namespace App\Exports;

use App\Models\Trail;
use App\User;
use Exception;
use Carbon\Carbon;
use App\ModuleUserType;
use Illuminate\Support\Facades\DB;
use App\ModuleableType;
use App\Models\TrailStar;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TrailsStatementExport implements FromQuery, WithMapping, WithHeadings
{
    use Exportable;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function query()
    {
        $request = $this->request;
        $start_time = $request->get('start_time',Carbon::now()->addDay(-7)->toDateTimeString());
        $end_time = $request->get("end_time",Carbon::now()->toDateTimeString());
        $type = $request->get('type',null);
        $department = $request->get('department',null);
        $department = $department == null ? null : hashid_decode($department);
        $arr[] = ['t.created_at','>=',Carbon::parse($start_time)->toDateString()];
        $arr[]  =   ['t.created_at','<=',Carbon::parse($end_time)->toDateString()];
        if($type != null){
            $arr[]  = ['t.type',$type];
        }
        if($department != null){
            $arr[] = ['du.department_id',$department];
        }
        return $trail = (new Trail())->setTable('t')->from('trails as t')
            ->select("t.id","t.type",'t.title','t.resource_type','resource','t.fee','t.status','t.priority',
                DB::raw('u.name as principal_user'),
                DB::raw("GROUP_CONCAT(DISTINCT s.name) as star_name"),
                DB::raw("GROUP_CONCAT(DISTINCT d.name) as deparment_name")
            )
            ->leftJoin('trail_star as ts',function ($join){
                $join->on('ts.trail_id','=','t.id')
                    ->where('ts.starable_type',ModuleableType::STAR)//艺人
                    ->where('ts.type',TrailStar::EXPECTATION);//目标
            })
            ->leftJoin('stars as s','s.id','=','ts.starable_id')
            ->leftJoin('module_users as mu',function ($join){
                $join->on('mu.moduleable_id','=','s.id')
                    ->where('mu.moduleable_type',ModuleableType::STAR)//艺人
                    ->where('mu.type',ModuleUserType::BROKER);//经纪人
            })
            ->leftJoin('users as u1','u1.id','=','mu.user_id')
            ->leftjoin('department_user as du','du.user_id','=','u1.id')
            ->leftjoin('departments as d','d.id','=','du.department_id')
            ->leftJoin('users as u','u.id','=','t.principal_id')
            ->groupBy('t.id')
            ->whereIn('t.type',[Trail::TYPE_MOVIE,Trail::TYPE_VARIETY,Trail::TYPE_ENDORSEMENT])
            ->where($arr);
//        foreach ($trails as &$trail){
//            if(is_numeric($trail['resource'])){
//                $user = User::select('name')->find($trail['resource']);
//                $trail['resource'] = $user['name'];
//            }
//        }
               }


    /**
     * @param Trail $trail
     * @return array
     */
    public function map($trail): array
    {
        $trail_type = $this->type($trail -> type);
        $title = $trail ->title;
        $resource_type = $this->resourceType($trail->resource_type);
        $deparment_name = $trail->deparment_name;
        $star_name = $trail->star_name;
        $fee = $trail->fee;

        if ($trail->status == '1') {
            $status = '开始接洽';
        } elseif ($trail->status == '2') {
            $status = '主动拒绝';
        } elseif ($trail->status == '3') {
            $status = '客户拒绝';
        }elseif ($trail->status == '4') {
            $status = '进入谈判';
        }elseif ($trail->status == '5') {
            $status = '意向签约';
        }elseif ($trail->status == '6') {
            $status = '签约中';
        } elseif ($trail->status == '7') {
            $status = '签约完成';
        }elseif ($trail->status == '8') {
            $status = '待执行';
        }elseif ($trail->status == '9') {
            $status = '在执行';
        }elseif ($trail->status == '10') {
            $status = '已执行';
        }elseif ($trail->status == '11') {
            $status = '客户回款';
        }elseif ($trail->status == '12') {
            $status = '客户反馈分析及项目复盘';
        }else{
            $status = '拒绝类型错误';
        }
        $status = $status;
        $priority = (new Trail())->getPriority($trail->priority);
        $principal_user = $trail->principal_user;
   return [
            $trail_type,
            $title,
            $resource_type,
            $deparment_name,
            $star_name,
            $fee,
            $status,
            $priority,
            $principal_user,

        ];
    }

    public function headings(): array
    {
        return [
            '线索类别',
            '线索名称',
            '线索来源',
            '组别',
            '目标艺人',
            '预计订单收入',
            '线索状态',
            '优先级',
            '负责人 '
        ];
    }

    /**
     * @param string $type
     * @return string $type
     */
    private function resourceType($type)
    {
        switch ($type) {
            case 1:
                $type = '商务邮箱';
                break;
            case 2:
                $type = '工作室邮箱';
                break;
            case 3:
                $type = '微信公众号';
                break;
            case 4:
                $type = '员工';
                break;
            case 5:
                $type = '公司高管';
                break;
            case 6:
                $type = '纯中介';
                break;
            case 7:
                $type = '香港中介';
                break;
            case 8:
                $type = '台湾中介';
                break;
            case 9:
                $type = '复购直客';
                break;
            case 10:
                $type = '媒体介绍';
                break;
            case 11:
                $type = '公关or广告公司';
                break;
        }
        return $type;
    }
    private  function type($type)
    {
        switch ($type) {
            case 1:
                $type = '影视';
                break;
            case 2:
                $type = '综艺';
                break;
            case 3:
                $type = '商务';
                break;
            case 4:
                $type = '商务';
                break;
        }
        return $type;
    }
    private function starsStr($stars): string
    {
        $starsStr = '';
        foreach ($stars as $star) {
            $starsStr .= $star->name ?? $star->nickname;
            $starsStr .= ',';
        }
        return substr($starsStr, 0, strlen($starsStr) - 1);
    }
    private function principal($principal_name)
    {
        $user = User::where('name', $principal_name)->first();
        if ($user)
            return $user->id;
        else
            return '';
    }
}
