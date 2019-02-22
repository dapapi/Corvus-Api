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
use Carbon\Carbon;
use App\Models\Trail;
use App\ModuleUserType;
use App\Models\TrailStar;

class ProjectsStatementExport implements FromQuery, WithMapping, WithHeadings
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
        $start_time = $request->get('start_time',Carbon::now()->addDay(-7)->toDateTimeString());
        $end_time = $request->get("end_time",Carbon::now()->toDateTimeString());
        $type = $request->get('type',null);
        $department = $request->get('department',null);
        $department = $department == null ? null : hashid_decode($department);
        $arr[] = ['p.created_at','>=',Carbon::parse($start_time)->toDateString()];
        $arr[]  =   ['p.created_at','<=',Carbon::parse($end_time)->toDateString()];
        if($department != null){
            $arr[] = ['d.id',$department];
        }
        if($type != null){
            $arr[]  = ['p.type',$type];
        }
        $project = (new Project())->setTable("p")->from("projects as p")
            ->leftJoin('users as u','u.id','=','p.principal_id')
            ->leftJoin('trail_star as ts',function ($join){
                $join->on('ts.trail_id','=','p.trail_id')
                    ->where('ts.starable_type',ModuleableType::STAR)//艺人
                    ->where('ts.type',TrailStar::EXPECTATION);//目标
            })
            ->leftJoin('trails as t','t.id','=','ts.trail_id')
            ->leftJoin('stars as s','s.id','=','ts.starable_id')
            ->leftJoin('module_users as mu',function ($join){
                $join->on('mu.moduleable_id','=','s.id')
                    ->where('mu.moduleable_type',ModuleableType::STAR)//艺人
                    ->where('mu.type',ModuleUserType::BROKER);//经纪人
            })
            ->leftJoin('contracts as co',function ($join){
                $join->on('co.project_id','=','p.id')
                    ->where('mu.type',ModuleUserType::BROKER);//经纪人
            })
            ->leftJoin('users as u1','u1.id','=','mu.user_id')
            ->leftjoin('department_user as du','du.user_id','=','u1.id')
            ->leftJoin('departments as d','d.id','=','du.department_id')
            ->whereIn('t.type',[Trail::TYPE_MOVIE,Trail::TYPE_VARIETY,Trail::TYPE_ENDORSEMENT])
            ->whereIn('p.type',[Project::TYPE_VARIETY,Project::TYPE_ENDORSEMENT,Project::TYPE_MOVIE])
            ->where($arr)
            ->groupBy('p.id')
//             $sql_with_bindings = str_replace_array('?', $peroject_list->getBindings(), $peroject_list->toSql());
//             dd($sql_with_bindings);
            ->select([
                DB::raw('p.id'),
                DB::raw("GROUP_CONCAT(distinct d.name) as deparment_name"),
                DB::raw('sum(distinct co.contract_money) as total_contract_money'),
                DB::raw("GROUP_CONCAT(distinct s.name) as star_name"),
                'p.status','p.type','p.title',
                DB::raw('u.name as principal_name'),
                'p.trail_id'
            ]);
        return $project;

    }

    /**
     * @param Blogger $blogger
     * @return array
     */
    public function map($project): array
    {
        $project_type = $this->type($project->type);
        $title = $project->title;
        $deparment_name = $project->deparment_name;
        $star_name = $project->star_name;
        $total_contract_money = $project->total_contract_money;
        $status = $project->status;
        $principal_name = $project->principal_name;
        return[
            $project_type,
            $title,
            $deparment_name,
            $star_name,
            $total_contract_money,
            '',
            $status,
            $principal_name

        ];
    }

    public function headings(): array
    {
        return [
            '项目类型',
            '项目名称',
            '组别',
            '签约艺人',
            '合同金额',
            '项目成本',
            '项目进度',
            '负责人'




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
                $type = '影视';
                break;
            case 2:
                $type = '综艺';
                break;
            case 3:
                $type = '商务';
                break;
            case 4:
                $type = 'papi';
                break;
            case 5:
                $type = '基础';
                break;
        }
        return $type;
    }
}
