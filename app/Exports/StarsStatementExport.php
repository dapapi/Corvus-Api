<?php

namespace App\Exports;
use Illuminate\Support\Facades\Auth;
use App\Models\Star;
use Carbon\Carbon;
use App\CommunicationStatus;
use App\ModuleableType;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use App\SignContractStatus;
use App\ModuleUserType;
use App\Models\TrailStar;
use App\OperateLogMethod;
use Illuminate\Support\Facades\DB;
use Qiniu\Http\Request;

class StarsStatementExport implements FromQuery, WithMapping, WithHeadings
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
        $sign_contract_status = $request->get('sign_contract_status',null);
        $department = $request->get("departmnet",null);
        $department = $department == null ? null : hashid_decode($department);
        $target_star = $request->get('target_star',null);
        $target_star = $target_star == null ? null :hashid_decode($target_star);
        $type = $request->get('type',null);
        $arr[] = ['s.created_at','>=',Carbon::parse($start_time)->toDateString()];
        $arr[]  =   ['s.created_at','<=',Carbon::parse($end_time)->toDateString()];
        $arr[] = ['s.sign_contract_status',$sign_contract_status];
        if($type != null){
            $arr[] = ['p.type','=',$type];
            $arr[]  =   ['t.type',$type];
        }

        if($department != null){
            $arr[] = ['d.id','=',$department];
        }
        if($target_star != null){
            $arr[] = ['s.id',$target_star];
        }
        //签约中
        if($sign_contract_status == SignContractStatus::SIGN_CONTRACTING){
            $sub_query = DB::table("operate_logs")
                ->groupBy("created_at")
                ->select(DB::raw("max(created_at) as created_at,id,logable_id,logable_type,method"));

            $stars = (new Star())->setTable("s")->from("stars as s")
                ->leftJoin(DB::raw("({$sub_query->toSql()}) as op"),function ($join){
                    $join->on('op.logable_id','=','s.id')
                        ->where('op.logable_type','=',ModuleableType::STAR)//可能有问题
//                        ->where('op.method','=',OperateEntity::UPDATED_AT);
                        ->where('op.method','=',OperateLogMethod::FOLLOW_UP);
                })
                ->where($arr)
                ->groupBy('s.id')
                ->select('s.sign_contract_status','s.name','s.birthday','s.source','s.communication_status','s.created_at','op.created_at as last_update_at');
//                            $sql_with_bindings = str_replace_array('?', $stars->getBindings(), $stars->toSql());
//                            dd($sql_with_bindings);
              //  ->get();
            return $stars;
        }else {//已签约/解约
//            $contract = (new Star())->get(['id']);
//            $co = Contract::where('star_type','stars')->get();
//           foreach($contract as $key => $val){
//               $val
//           }

            //合同，预计订单收入，花费金额都没查呢
            $stars = (new Star())->setTable("s")->from("stars as s")
                ->leftJoin("module_users as mu", function ($join) {
                    $join->on('mu.moduleable_id', '=', 's.id')
                        ->where('mu.moduleable_type', '=', ModuleableType::STAR)//艺人
                        ->where('mu.type', '=', ModuleUserType::BROKER);//经纪人
                })->leftJoin("department_user as du", 'du.user_id', '=', 'mu.user_id')
                ->leftJoin('departments as d', 'd.id', '=', 'du.department_id')
                ->leftJoin("trail_star as ts", function ($join) {
                    $join->on('ts.starable_id', '=', 's.id')
                        ->where('ts.starable_type', '=', ModuleableType::STAR)//艺人
                        ->where('ts.type', TrailStar::EXPECTATION);//目标
                })
                ->leftJoin("contracts as co", function ($join) {
                    //     $join->on('co.stars','like','s.id')//艺人
                    //     $join->on('co.stars','<', '(LENGTH(s.id)-LENGTH(REPLACE(s.id,\',\',\'\'))+1) ')
                    $join->whereRaw("FIND_IN_SET(s.id,stars)")
                        ->where('co.star_type', '=', 'stars');
                })
                ->leftJoin('trails as t', 't.id', '=', 'ts.trail_id')
                ->leftJoin('projects as p', 'p.trail_id', '=', 'ts.trail_id')
                ->where($arr)
                ->groupBy('s.id')
//                               $sql_with_bindings = str_replace_array('?', $stars->getBindings(), $stars->toSql());
//        dd($sql_with_bindings);
                ->select([
                    's.id', 's.name', 'sign_contract_status',
                    DB::raw('sum(distinct t.fee) as total_fee'),
                    DB::raw('sum(distinct co.contract_money) as total_contract_money'),
                    //         DB::raw('SUBSTRING_INDEX(SUBSTRING_INDEX(leave_entries.dates, \',\', numbers.n), \',\', -1)  as total_contract_money'),
                    DB::raw("count(distinct ts.id) as trail_total"),
                    DB::raw("count(distinct p.id) as project_total"),
                    DB::raw("GROUP_CONCAT(DISTINCT d.name) as department_name")
                ]);


            return $stars;

        }
    }

    /**
     * @param Blogger $blogger
     * @return array
     */
    public function map($star): array
    {
        $request = $this->request;
        $name = $star->name;

        $source =  $this->source($star->source);
        $department_name = $star->department_name;
        $total_fee = $star->total_fee;
        $total_contract_money = $star->total_contract_money;
        $communication_status = CommunicationStatus::getStr($star->communication_status);
        $created_at = $star->created_at;
        $last_update_at = $star->last_update_at;
        $sign_contract_status = $request->get('sign_contract_status',null);
        if($sign_contract_status == SignContractStatus::SIGN_CONTRACTING) {
            $birthday =$this->howOld($star->birthday);
            return [
                $name,
                $birthday,
                $source,
                $communication_status,
                $created_at,
                $last_update_at
            ];

        }else{
            return [
                $department_name,
                $name,
                $total_fee,
                $total_contract_money
            ];
        }

    }

    public function headings(): array
    {

        $request = $this->request;
        $sign_contract_status = $request->get('sign_contract_status',null);
        if($sign_contract_status == SignContractStatus::SIGN_CONTRACTING) {
                return [
                    '姓名',
                    '年龄',
                    '艺人来源',
                    '沟通状态',
                    '录入时间',
                    '最后跟进时间'


                ];
        }else{
            return [
                '组别',
                '姓名',
                '预计订单收入',
                '合同金额',
                '花费金额'



            ];
        }
    }
    private function getStr($key)
    {
        $start = '';
        switch ($key) {
            case 1:
                $start = '是';
                break;
            case 2:
                $start = '否';
                break;
        }
        return $start;
    }
    /**
     * @param string $type
     * @return string $type
     */
    private function howOld($birth) {
        list($birthYear, $birthMonth, $birthDay) = explode('-', date($birth));
        list($currentYear, $currentMonth, $currentDay) = explode('-', date('Y-m-d'));
        $age = $currentYear - $birthYear - 1;
        if($currentMonth > $birthMonth || $currentMonth == $birthMonth && $currentDay >= $birthDay)
            $age++;

        return $age;
    }

    private function source($source)
    {
        switch ($source) {
            case 1:
                $source = '线上';
                break;
            case 2:
                $source = '线下';
                break;
            case 3:
                $source = '抖音';
                break;
            case 4:
                $source = '微博';
                break;
            case 5:
                $source = '陈赫';
                break;
            case 6:
                $source = '北电';
                break;
            case 7:
                $source = '杨光';
                break;
            case 8:
                $source = '中戏';
                break;
            case 9:
                $source = 'papitube推荐';
                break;
            case 10:
                $source = '地标商圈';
                break;
        }
        return $source;
    }
    /**
     * @param string $type
     * @return string $type
     */
    private function platform($platform)
    {
        switch ($platform) {
            case 1:
                $platform = '微博';
                break;
            case 2:
                $platform = '抖音';
                break;
            case 3:
                $platform = '小红书';
                break;
            case 4:
                $platform = '全平台';
                break;
        }
        return $platform;
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
