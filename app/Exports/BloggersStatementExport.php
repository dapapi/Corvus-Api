<?php

namespace App\Exports;

use App\Models\Blogger;
use Maatwebsite\Excel\Concerns\Exportable;
use App\ModuleableType;
use App\Repositories\ReportFormRepository;
use App\SignContractStatus;
use App\OperateLogMethod;
use Carbon\Carbon;
use App\ModuleUserType;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Qiniu\Http\Request;
use App\Models\TrailStar;
use Illuminate\Support\Facades\DB;

class BloggersStatementExport implements FromQuery, WithMapping, WithHeadings
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
        $start_time = $request->get('start_time', Carbon::now()->addDay(-7)->toDateTimeString());
        $end_time = $request->get("end_time", Carbon::now()->toDateTimeString());
        $sign_contract_status = $request->get('sign_contract_status', null);
        $department = $request->get('department', null);
        $target_star = $request->get('target_star', null);
        $target_star = $target_star == null ? null : hashid_decode($target_star);
        $department = $department == null ? null : hashid_decode($department);
        $arr[] = ['b.created_at', '>=', Carbon::parse($start_time)->toDateString()];
        $arr[] = ['b.created_at', '<=', Carbon::parse($end_time)->toDateString()];
        $arr[] = ['b.sign_contract_status', $sign_contract_status];
        if ($department != null) {
            $arr[] = ['d.id', $department];
        }
        if ($target_star != null) {
            $arr[] = ['s.id', $target_star];
        }
        //签约中
        if ($sign_contract_status == SignContractStatus::SIGN_CONTRACTING) {
            $sub_query = DB::table("operate_logs")->groupBy("created_at")->select(DB::raw("max(created_at) as created_at,id,logable_id,logable_type,method"));
            $blogger = (new Blogger())->setTable('b')->from('bloggers as b')
                ->leftJoin(DB::raw("({$sub_query->toSql()}) as op"), function ($join) {
                    $join->on('op.logable_id', '=', 'b.id')
                        ->where('op.logable_type', '=', ModuleableType::BLOGGER)//可能有问题
                        //   ->where('op.method','=',OperateEntity::UPDATED_AT);
                        ->where('op.method', '=', OperateLogMethod::FOLLOW_UP);
                })->leftJoin("blogger_types as bt", "bt.id", "b.type_id")
                ->where($arr)
                ->groupBy('b.id')
                ->select('b.nickname', 'bt.name as type_id', 'b.communication_status', 'b.created_at', 'op.created_at as last_update_at');
            //->get();
            return $blogger;
//            $sql_with_bindings = str_replace_array('?', $bloggers->getBindings(), $bloggers->toSql());
//        dd($sql_with_bindings);
        } else {
            //合同，预计订单收入，花费金额都没查呢
            $blogger = (new Blogger())->setTable("b")->from("bloggers as b")
                ->leftJoin("module_users as mu", function ($join) {
                    $join->on('mu.moduleable_id', '=', 'b.id')
                        // 从 star 修改成  blogger    张
                        ->where('mu.moduleable_type', '=', ModuleableType::BLOGGER)//艺人
                        // 从 star 修改成  blogger    张
                        ->where('mu.type', '=', ModuleUserType::PRODUCER);//制作人
                })->leftJoin("department_user as du", 'du.user_id', '=', 'mu.user_id')
                ->leftJoin('departments as d', 'd.id', '=', 'du.department_id')
                ->leftJoin("contracts as co", function ($join) {
                    $join->whereRaw("FIND_IN_SET(b.id,stars)")
                        ->where('co.star_type', '=', 'bloggers');
                })
                ->leftJoin("trail_star as ts", function ($join) {
                    $join->on('ts.starable_id', '=', 'b.id')
                        ->where('ts.starable_type', '=', ModuleableType::BLOGGER)//艺人
                        ->where('ts.type', TrailStar::EXPECTATION);//目标
                })->leftJoin('trails as t', 't.id', '=', 'ts.trail_id')
                ->leftJoin('projects as p', 'p.trail_id', '=', 't.id')
                ->where($arr)
                ->groupBy('b.id')
//                       $sql_with_bindings = str_replace_array('?', $blogger->getBindings(), $blogger->toSql());
//        dd($sql_with_bindings);
            ->select([
                    'b.id','b.nickname','t.fee','sign_contract_status',
                    // 少了合同金额    花费金额
                    DB::raw('sum(distinct t.fee) as total_fee'),
                    DB::raw('sum(distinct co.contract_money) as total_contract_money'),
                    DB::raw("count(ts.id) as trail_total"),
                    DB::raw("count(p.id) as project_total"),
                    DB::raw("GROUP_CONCAT(DISTINCT d.name) as department_name")
                ]);
            return $blogger;
//                ->get([
//                    'b.id','b.nickname','t.fee','sign_contract_status',
//                    // 少了合同金额    花费金额
//                    DB::raw('sum(distinct t.fee) as total_fee'),
//                    DB::raw('sum(distinct co.contract_money) as total_contract_money'),
//                    DB::raw("count(ts.id) as trail_total"),
//                    DB::raw("count(p.id) as project_total"),
//                    DB::raw("GROUP_CONCAT(DISTINCT d.name) as department_name")
//                ]);


        }
    }

    /**
     * @param Blogger $blogger
     * @return array
     */
    public function map($blogger): array
    {
        $request = $this->request;
        $sign_contract_status = $request->get('sign_contract_status', null);
        $department_name = $blogger->department_name;
        $communication_status = $this->sign($blogger->communication_status);
        $created_at = $blogger->created_at;
        $last_update_at = $blogger->last_update_at;
        $type_id = $blogger->type_id;
        $nickname = $blogger->nickname;
        $trail_total = $blogger->trail_total;
        $total_fee = $blogger->total_fee;
        $total_contract_money = $blogger->total_contract_money;
        if ($sign_contract_status == SignContractStatus::SIGN_CONTRACTING) {
            return [
                $nickname,
                $type_id,
                $communication_status,
                $created_at,
                $last_update_at,


            ];


        }else{
        return [
            $department_name,
            $nickname,
            $trail_total,
            $total_fee,
            $total_contract_money,


          ];
        }

    }

    public function headings(): array
    {


        $request = $this->request;
        $sign_contract_status = $request->get('sign_contract_status', null);

        if ($sign_contract_status == SignContractStatus::SIGN_CONTRACTING) {

            return [
                '昵称',
                '类型',
                '沟通状态',
                '录入时间',
                '最后跟进时间'


            ];

        }else{

            return [
                '制作组',
                '昵称',
                '线索数量',
                '预计订单收入',
                '合同金额',
                '花费金额'


            ];
        }

    }
    /**
     * @param string $type
     * @return string $type
     */
    private function sign($type)
    {
        switch ($type) {
            case 1:
                $type = '初步接触';
                break;
            case 2:
                $type = '沟通中';
                break;
            case 3:
                $type = '合同中';
                break;
            case 4:
                $type = '沟通完成';
                break;
        }
        return $type;
    }
    /**
     * @param string $type
     * @return string $type
     */
    private function plat($type)
    {
        switch ($type) {
            case 1:
                $type = '微博';
                break;
            case 2:
                $type = '抖音';
                break;
            case 3:
                $type = '小红书';
                break;
            case 4:
                $type = '全平台';
                break;
        }
        return $type;
    }
    /**
     * @param string $type
     * @return string $type
     */
    private function type($type)
    {
        switch ($type) {
            case 1:
                $type = '搞笑剧情';
                break;
            case 2:
                $type = '美食';
                break;
            case 3:
                $type = '美妆';
                break;
            case 4:
                $type = '颜值';
                break;
            case 5:
                $type = '生活方式';
                break;
            case 6:
                $type = '生活测评';
                break;
            case 7:
                $type = '萌宠';
                break;
            case 8:
                $type = '时尚';
                break;
            case 9:
                $type = '旅行';
                break;
            case 10:
                $type = '动画';
                break;
            case 11:
                $type = '母婴';
                break;
            case 12:
                $type = '情感';
                break;
            case 13:
                $type = '摄影';
                break;
            case 14:
                $type = '舞蹈';
                break;
            case 15:
                $type = '影视';
                break;
            case 16:
                $type = '游戏';
                break;
            case 17:
                $type = '数码';
                break;
            case 18:
                $type = '街访';
                break;
            case 19:
                $type = '其他';
                break;
        }
        return $type;
    }
}
