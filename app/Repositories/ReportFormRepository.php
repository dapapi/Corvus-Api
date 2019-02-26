<?php

namespace App\Repositories;

use App\Models\Blogger;
use App\Models\Client;
use App\Models\Contact;
use App\OperateLogMethod;
use App\Models\OperateEntity;
use App\Models\OperateLog;
use App\Models\Project;
use App\Models\Star;
use App\Models\Trail;
use App\Models\TrailStar;
use App\ModuleableType;
use App\ModuleUserType;
use App\Models\Contract;
use App\SignContractStatus;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportFormRepository
{
    public function CommercialFunnelReportFrom($start_time,$end_time)
    {
        $start_time = Carbon::parse($start_time)->toDateString();//查询周期开始时间
        $end_time = Carbon::parse($end_time)->toDateString();//查询周期结束时间
        $current_industry_trail_number = $this->getEveryIndustryTrailConfrimNumber($start_time,$end_time);//获取线索接触数量
        $current_industry_trail_confirm_number = $this->getEveryIndustryTrailConfrimNumber($start_time,$end_time,Trail::STATUS_CONFIRMED);//获取线索达成数量

        //获取查询周期
        $rang = Carbon::parse($start_time)->diffInDays($end_time);
        $prev_range_end = Carbon::parse($end_time)->addDay(-($rang+1))->toDateString();//上一次查询周期线索接触数量
        $prev_range_start = Carbon::parse($start_time)->addDay(-($rang+1))->toDateString();//上一次查询周期线索达成数量

        $prev_industry_trail_number = $this->getEveryIndustryTrailConfrimNumber($prev_range_start,$prev_range_end);//上一年查询周期线索接触数量
        $prev_industry_trail_confirm_number = $this->getEveryIndustryTrailConfrimNumber($prev_range_start,$prev_range_end,Trail::STATUS_CONFIRMED);//上一年查询周期线索达成数量

        $prev_year_start = Carbon::parse($start_time)->addYear(-1)->toDateString();
        $prev_year_end = Carbon::parse($end_time)->addYear(-1)->toDateString();
        $prev_year_industry_trail_number = $this->getEveryIndustryTrailConfrimNumber($prev_year_start,$prev_year_end);
        $prev_year_industry_trail_confirm_number = $this->getEveryIndustryTrailConfrimNumber($prev_year_start,$prev_year_end,Trail::STATUS_CONFIRMED);
        //行业
        $industry_data = $this->computeDate(
            $current_industry_trail_number,
            $prev_industry_trail_number,
            $prev_year_industry_trail_number,
            $current_industry_trail_confirm_number,
            $prev_industry_trail_confirm_number,
            $prev_year_industry_trail_confirm_number
        );

        //根据合作类行进行统计
        //当前统计周期数据
        $current_cooperation_type_trail_numer = $this->getEveryCooperationTypeTrailNumber($start_time,$end_time);
        $current_confrim_cooperation_type_trail_numer = $this->getEveryCooperationTypeTrailNumber($start_time,$end_time,Trail::STATUS_CONFIRMED);
//
        //环比统计周期数据
        $prev_cooperation_trail_numer = $this->getEveryCooperationTypeTrailNumber($prev_range_start,$prev_range_end);

        $prev_confirm_cooperation_trail_numer = $this->getEveryCooperationTypeTrailNumber($prev_range_start,$prev_range_end,Trail::STATUS_CONFIRMED);
//
        //同比统计周期数据
        $prev_year_cooperation_trail_number = $this->getEveryCooperationTypeTrailNumber($prev_year_start,$prev_range_start);
        $prev_year_confirm_cooperation_trail_number = $this->getEveryCooperationTypeTrailNumber($prev_year_start,$prev_range_start,Trail::STATUS_CONFIRMED);


        $cooperation_data =  $this->computeDate(
            $current_cooperation_type_trail_numer,
            $prev_cooperation_trail_numer,
            $prev_year_cooperation_trail_number,
            $current_confrim_cooperation_type_trail_numer,
            $prev_confirm_cooperation_trail_numer,
            $prev_year_confirm_cooperation_trail_number
        );
        //根据线索来源统计
        $current_resource_type_trail_number = $this->getEverySourceTrailNumber($start_time,$end_time);
        $current_confrim_resource_type_trail_number = $this->getEverySourceTrailNumber($start_time,$end_time,Trail::STATUS_CONFIRMED);
        $prev_resource_type_trail_number = $this->getEverySourceTrailNumber($prev_range_start,$prev_range_end);
        $prev_confrim_resource_type_trail_number = $this->getEverySourceTrailNumber($prev_range_start,$prev_range_end,Trail::STATUS_CONFIRMED);
        $prev_year_resource_type_trail_number = $this->getEverySourceTrailNumber($prev_year_start,$prev_year_end);
        $prev_year_confrim_resource_type_trail_number = $this->getEverySourceTrailNumber($prev_year_start,$prev_year_end,Trail::STATUS_CONFIRMED);
        $resource_type_data = $this->computeDate(
            $current_resource_type_trail_number,
            $prev_year_resource_type_trail_number,
            $prev_resource_type_trail_number,
            $current_confrim_resource_type_trail_number,
            $prev_confrim_resource_type_trail_number,
            $prev_year_confrim_resource_type_trail_number
        );
        //根据优先级
        $current_priority_trail_number = $this->getEveryPriorityTrailNumber($start_time,$end_time);
        $current_confrim_priority_trail_number = $this->getEveryPriorityTrailNumber($start_time,$end_time,Trail::STATUS_CONFIRMED);
        $prev_priority_trail_number = $this->getEveryPriorityTrailNumber($prev_range_start,$prev_range_end);
        $prev_confrim_priority_trail_number = $this->getEveryPriorityTrailNumber($prev_range_start,$prev_range_end,Trail::STATUS_CONFIRMED);
        $prev_year_priority_trail_number = $this->getEveryPriorityTrailNumber($prev_year_start,$prev_year_end);
        $prev_year_confrim_priority_trail_number = $this->getEveryPriorityTrailNumber($prev_year_start,$prev_year_end,Trail::STATUS_CONFIRMED);
        $priority_data = $this->computeDate(
            $current_priority_trail_number,
            $prev_priority_trail_number,
            $prev_year_priority_trail_number,
            $current_confrim_priority_trail_number,
            $prev_confrim_priority_trail_number,
            $prev_year_confrim_priority_trail_number
        );
        return [
                "sum"   =>  $industry_data['sum'],
                "confirm_sum"  =>  $industry_data['confirm_sum'],
                "ring_ratio_increment_sum"  =>  $industry_data['ring_ratio_increment_sum'],
                "annual_ratio_increment_sum"    =>  $industry_data['annual_ratio_increment_sum'],
                "confirm_ratio_increment_sum"  =>  $industry_data['confirm_ratio_increment_sum'],
                "confirm_annual_increment_sum" =>  $industry_data['confirm_annual_increment_sum'],
                "customer_conversion_rate_sum"  =>  $industry_data['customer_conversion_rate_sum'],//客户转化率总和
                "ratio_sum"    =>  $industry_data['ratio_sum'],//数量占比总和
                "data"  =>  [
                    "industry_data"    =>  $industry_data['data'],
                    "cooperation_data" =>  $cooperation_data['data'],
                    "resource_type_data"   =>  $resource_type_data['data'],
                    "priority_data"    =>  $priority_data['data']
                ]

            ];
    }

    /**
     * @param $curr 当前统计周期
     * @param $prev 上一统计周期
     * @param $prev_year 上一年统计周期
     */
    private function computeDate($curr,$prev,$prev_year,$curr_confirm,$prev_confirm,$prev_year_confirm){
        //计算接触数量总数
        $sum = array_sum(array_column($curr->toArray(),'number'));
        $confirm_sum = array_sum(array_column($curr_confirm->toArray(),'number'));
//        dd($current_industry_trail_number);
        //计算数量占比,计算同比
        array_map(function ($v) use ($sum,$prev,$prev_year){
            $v->ratio = $sum == 0 ? 0 : $v->number/$sum; //数量占比

            //获取行业上一周期对应的接触数量
            $prev_arr = $prev->toArray();
//            dd($prev_arr);
            $ring_key = array_search(intval($v->id),array_column($prev_arr,'id'));
            $v->ring_ratio_increment = $v->number-$prev_arr[$ring_key]->number;//接触环比增量

            //获取同比增量
            $prev_year_arr = $prev_year->toArray();
            $annual_key = array_search(intval($v->id),array_column($prev_year_arr,'id'));
            $v->annual_increment = $v->number-$prev_year_arr[$annual_key]->number;//接触同比增量
            return $v;
        },$curr->toArray());
        //计算环比
        array_map(function ($v) use ($prev_confirm,$prev_year_confirm){
            $prev_confirm_arr = $prev_year_confirm->toArray();
            $prev_year_confirm_arr = $prev_year_confirm->toArray();

            //环比增量
            $prev_confirm_key = array_search($v->id,array_column($prev_confirm_arr,"id"));
            $v->confirm_ratio_increment = $v->number-$prev_confirm_arr[$prev_confirm_key]->number;

            //同比增量
            $prev_year_confirm_key = array_search($v->id,array_column($prev_year_confirm_arr,"id"));
            $v->confirm_annual_increment = $v->number - $prev_year_confirm_arr[$prev_year_confirm_key]->number;
            return $v;
        },$curr_confirm->toArray());
        //合并两个数组 //将接触环比增量，接触同比增量，达成环比增量，达成同比增量算出总和
        $ring_ratio_increment_sum = 0;//接触环比总和
        $annual_ratio_increment_sum = 0;//接触同比总和
        $confirm_ratio_increment_sum = 0;//达成环比
        $confirm_annual_increment_sum = 0;//达成同比
        array_map(function ($v) use ($curr_confirm,
                                    &$ring_ratio_increment_sum,&$annual_ratio_increment_sum,
                                    &$confirm_ratio_increment_sum,&$confirm_annual_increment_sum){
            $current_confirm_Arr = $curr_confirm->toArray();
            $ring_ratio_increment_sum += $v->ring_ratio_increment;
            $annual_ratio_increment_sum += $v->annual_increment;
            $v->ratio = floor($v->ratio*10000)/10000;
            $key = array_search($v->id,array_column($current_confirm_Arr,'id'));
            $v->confirm_ratio_increment   =   $current_confirm_Arr[$key]->confirm_ratio_increment; //达成环比增量
            $v->confirm_annual_increment  =   $current_confirm_Arr[$key]->confirm_annual_increment; //达成同比增量
            $v->customer_conversion_rate = $v->number == 0? 0 :$current_confirm_Arr[$key]->number / $v->number;//客户转化率
            $v->customer_conversion_rate = floor($v->customer_conversion_rate*10000)/10000;
            $v->confirm_number = $current_confirm_Arr[$key]->number;//达成数量
            $confirm_ratio_increment_sum += $v->confirm_ratio_increment;
            $confirm_annual_increment_sum += $v->confirm_annual_increment;
            return $v;
        },$curr->toArray());

        return [
            "sum"   =>  $sum,
            "confirm_sum"  =>  $confirm_sum,
            "ring_ratio_increment_sum"  =>  $ring_ratio_increment_sum,
            "annual_ratio_increment_sum"    =>  $annual_ratio_increment_sum,
            "confirm_ratio_increment_sum"  =>  $confirm_ratio_increment_sum,
            "confirm_annual_increment_sum" =>  $confirm_annual_increment_sum,
            "ratio_sum"    =>  $sum == 0 ? 0 : $sum/$sum,//数量占比总和
            "customer_conversion_rate_sum" =>  $sum == 0 ? 0:floor(($confirm_sum/$sum)*10000)/10000,//客户转化率总和
            "data"  =>  $curr
        ];
    }
    //根据优先级
    public function getEveryPriorityTrailNumber($start_time,$end_time,$status=null){
        $arr = [];
        $arr[] = ['t.created_at','>=',$start_time];
        $arr[] = ['t.created_at','<=',$end_time];
        if($status != null){
            $arr[] = ['status',$status];
        }
        $subquery = DB::table(DB::raw("data_dictionaries as dd"))->where('parent_id',49)->select('val as id','name');
        $sub_query2 = DB::table("trails as t")->select("t.priority",DB::raw("count(t.id) as number"))->where($arr)->groupBy("t.priority");
        $result = DB::table(DB::raw("({$sub_query2->toSql()}) as t1"))->rightJoin(DB::raw("({$subquery->toSql()}) as t2"),"t2.id","=","t1.priority")
            ->mergeBindings($sub_query2)
            ->mergeBindings($subquery)
            ->get();
        return $result;
    }
    //销售线索来源维度来获取数据
    public function getEverySourceTrailNumber($start_time,$end_time,$status=null)
    {
        $arr = [];
        $arr[] = ['t.created_at','>=',$start_time];
        $arr[] = ['t.created_at','<=',$end_time];
        if($status != null){
            $arr[] = ['status',$status];
        }
        $subquery = DB::table(DB::raw("data_dictionaries as dd"))->where('parent_id',37)->select('val as id','name');
        $sub_query2 = DB::table("trails as t")->select("t.resource_type",DB::raw("count(t.id) as number"))->where($arr)->groupBy("t.resource_type");
        return DB::table(DB::raw("({$sub_query2->toSql()}) as t1"))->rightJoin(DB::raw("({$subquery->toSql()}) as t2"),"t2.id","=","t1.resource_type")
            ->mergeBindings($sub_query2)
            ->mergeBindings($subquery)
            ->get();

    }

    //获取合作类型的达成数量
    private function getEveryCooperationTypeTrailNumber($start_time,$end_time,$status=null)
    {
        $arr = [];
        $arr[] = ['t.created_at','>=',$start_time];
        $arr[] = ['t.created_at','<=',$end_time];
        if($status != null){
            $arr[] = ['status',$status];
        }
        $sub_query = "SELECT val as id,name FROM data_dictionaries where parent_id = 28";
//        $sub_query = "SELECT (@num := @num + 1) as id from trails,(SELECT @num := 0) t1 limit 8";
        $sub_query2 = DB::table("trails as t")->select("t.cooperation_type",DB::raw("count(t.id) as number"))->where($arr)->groupBy("t.cooperation_type");

        return DB::table(DB::raw("({$sub_query2->toSql()}) as t1 "))->rightJoin(DB::raw("({$sub_query}) as t2"),"t2.id","=","t1.cooperation_type")
//            ->mergeBindings($sub_query)
            ->mergeBindings($sub_query2)
            ->get();

    }
    //获取每个行业的达成数量
    private function getEveryIndustryTrailConfrimNumber($start_time,$end_time,$status=null)
    {
        $arr = [];
        $arr[] = ['t.created_at','>=',$start_time];
        $arr[] = ['t.created_at','<=',$end_time];
        if($status != null){
            $arr[] = ['status',$status];
        }
        //一个线索只有一个行业
        //DB::connection()->enableQueryLog();
        //子查询，在查询时间内的线索
        $subquery = DB::table("trails as t")
            ->where($arr)
            ->select("t.industry_id","t.id");

        return DB::table(DB::raw("({$subquery->toSql()}) as tt"))
            ->rightJoin("industries as i",'i.id','=','tt.industry_id')
            ->mergeBindings($subquery)
            ->groupBy("i.id")//根据行业分组
            ->get([//每个行业线索的数量number 名字name，行业id
                DB::raw("count(tt.id) as number"),"i.id","i.name"
            ]);

    }

    //商务漏斗，分析留存率
    public function salesFunnel($start_time,$end_time)
    {
        //根据状态分组统计销售线索个状态下
        $staus_trail_number = Trail::select("status",DB::raw("count(id) as total"))
            ->where('created_at','>=',Carbon::parse($start_time)->toDateString())
            ->where('created_at','<=',Carbon::parse($end_time)->toDateString())
            ->groupBy("status")->get();
        $status_number = [];
        foreach ($staus_trail_number as $value){
            $status_number[$value['status']] = $value['total'];
        }
        $sum = array_sum($status_number); //接触总量
        //主动拒绝后线索留存率
        $retention_trail_number = $sum-(
            isset($status_number[Trail::PROGRESS_REFUSE]) ? + $status_number[Trail::PROGRESS_REFUSE] : 0
            );
        $refuse_retention = $sum == 0 ? 0 : floor(($retention_trail_number / $sum)*10000)/10000;
        //客户拒绝后的留存率
        $retention_trail_number = $sum - (
            (isset($status_number[Trail::PROGRESS_REFUSE]) ? + $status_number[Trail::PROGRESS_REFUSE] : 0) +
            (isset($status_number[Trail::PROGRESS_CANCEL])?$status_number[Trail::PROGRESS_CANCEL] : 0)
            );
        $client_refuse_retention = $sum == 0 ? 0 : floor(($retention_trail_number / $sum)*10000)/10000;

        //进入谈判留存率
        $retention_trail_number = $sum - (
                (isset($status_number[Trail::PROGRESS_REFUSE]) ? + $status_number[Trail::PROGRESS_REFUSE] : 0) +
                (isset($status_number[Trail::PROGRESS_CANCEL])?$status_number[Trail::PROGRESS_CANCEL] : 0) +
                (isset($status_number[Trail::PROGRESS_TALK])?$status_number[Trail::PROGRESS_TALK] : 0)
            );
        $talk_retention = $sum == 0 ? 0 : floor(($retention_trail_number / $sum)*10000)/10000;

        //意向签约留存率 intention
        $retention_trail_number = $sum - (
                (isset($status_number[Trail::PROGRESS_REFUSE]) ? + $status_number[Trail::PROGRESS_REFUSE] : 0) +
                (isset($status_number[Trail::PROGRESS_CANCEL])?$status_number[Trail::PROGRESS_CANCEL] : 0) +
                (isset($status_number[Trail::PROGRESS_TALK])?$status_number[Trail::PROGRESS_TALK] : 0)+
                (isset($status_number[Trail::PROGRESS_INTENTION])?$status_number[Trail::PROGRESS_INTENTION]:0)
            );
        $intention_retention = $sum == 0 ? 0 : floor(($retention_trail_number / $sum)*10000)/10000;

        //签约完成留存率
        $retention_trail_number = $sum - (
                (isset($status_number[Trail::PROGRESS_REFUSE]) ? + $status_number[Trail::PROGRESS_REFUSE] : 0) +
                (isset($status_number[Trail::PROGRESS_CANCEL])?$status_number[Trail::PROGRESS_CANCEL] : 0) +
                (isset($status_number[Trail::PROGRESS_TALK])?$status_number[Trail::PROGRESS_TALK] : 0)+
                (isset($status_number[Trail::PROGRESS_INTENTION])?$status_number[Trail::PROGRESS_INTENTION]:0)+
                (isset($status_number[Trail::PROGRESS_SIGNING]) ? $status_number[Trail::PROGRESS_SIGNING] : 0)+
                (isset($status_number[Trail::PROGRESS_SIGNED]) ? $status_number[Trail::PROGRESS_SIGNED] : 0)
            );
        $signed_retention = $sum == 0 ? 0 : floor(($retention_trail_number / $sum)*10000)/10000;

        //归档
        $archive_trail_number = isset($status_number[Trail::PROGRESS_ARCHIVE]) ? $status_number[Trail::PROGRESS_ARCHIVE] : 0;
        $archive_retention = $sum == 0 ? 0 : floor(($archive_trail_number / $sum)*10000)/10000;
        //项目结算留存率
        return [
            "touch_total"   =>  $sum,//接触总量
            'refuse_retention'  =>  $refuse_retention,//主动拒绝
            'refuse_num'    =>  isset($status_number[Trail::PROGRESS_REFUSE]) ?  $status_number[Trail::PROGRESS_REFUSE] : 0,
            'client_refuse_retention'   =>  $client_refuse_retention,//客户拒绝
            "client_refuse_num" =>  isset($status_number[Trail::PROGRESS_CANCEL]) ?  $status_number[Trail::PROGRESS_CANCEL] : 0,
            'talk_retention'    =>  $talk_retention,//谈判留存
            'talk_num'  =>  isset($status_number[Trail::PROGRESS_TALK])?$status_number[Trail::PROGRESS_TALK] : 0,
            'intention_retention'   =>  $intention_retention,//意向签约
            'intention_num' =>  isset($status_number[Trail::PROGRESS_INTENTION])?$status_number[Trail::PROGRESS_INTENTION]:0,
            'signed_retention'    =>  $signed_retention,//签约完成留存率
            'signed_num'    =>  isset($status_number[Trail::PROGRESS_SIGNED]) ? $status_number[Trail::PROGRESS_SIGNED] : 0,
            //归档
            'archive_retention'    =>  $archive_retention,
            'archive_num'   =>  isset($status_number[Trail::PROGRESS_ARCHIVE]) ? $status_number[Trail::PROGRESS_ARCHIVE] : 0
        ];



    }

    /**
     * 线索报表
     * @param $start_time 开始时间
     * @param $end_time 结束时间
     * @param $type 线索类型
     * @param $department 经纪人所在部门
     */
    public function trailReportFrom($start_time,$end_time,$type=null,$department=null)
    {
        $arr[] = ['t.created_at','>=',Carbon::parse($start_time)->toDateString()];
        $arr[]  =   ['t.created_at','<=',Carbon::parse($end_time)->toDateString()];
        if($type != null){
            $arr[]  = ['t.type',$type];
        }
        if($department != null){
            $arr[] = ['du.department_id',$department];
        }
        $trails = (new Trail())->setTable('t')->from('trails as t')
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
            ->where($arr)->get();
            foreach ($trails as &$trail){
                if(is_numeric($trail['resource'])){
                    $user = User::select('name')->find($trail['resource']);
                    $trail['resource'] = $user['name'];
                }
            }
        return [
            'trail_total'   =>  count($trails),
            'fee_total' =>  array_sum(array_column($trails->toArray(),'fee')),
            'trail_list'    =>  $trails,
        ];
    }

    /**
     * 线索新增
     * @param $start_time
     * @param $end_time
     * @param null $department
     * @param null $target_star
     * @return array
     */
    public function newTrail($start_time,$end_time,$department=null,$target_star=null)
    {
        $arr[] = ['t.created_at','>=',Carbon::parse($start_time)->toDateString()];
        $arr[]  =   ['t.created_at','<=',Carbon::parse($end_time)->toDateString()];
        if($department != null){
            $arr[] = ['d.id',$department];
        }
        if($target_star != null){
            $arr[] = ['ts.starable_id',$target_star];
        }
        $trails = (new Trail())->setTable("t")->from('trails as t')
            ->leftJoin('trail_star as ts',function($join){
                $join->on('ts.trail_id','=','t.id')
                    ->where('ts.starable_type',ModuleableType::STAR)//艺人
                    ->where('ts.type',TrailStar::EXPECTATION);//目标
            })
            ->leftJoin('module_users as mu',function ($join){
                $join->on('mu.moduleable_id','=','ts.starable_id')
                    ->where('mu.moduleable_type',ModuleableType::STAR)//艺人
                    ->where('mu.type',ModuleUserType::BROKER);//经纪人
            })
            ->leftJoin('users as u','u.id','=','mu.user_id')
            ->leftjoin('department_user as du','du.user_id','=','u.id')
            ->leftJoin('departments as d','d.id','=','du.department_id')
            ->where($arr)
            ->whereIn('t.type',[Trail::TYPE_MOVIE,Trail::TYPE_VARIETY,Trail::TYPE_ENDORSEMENT])
            ->select(
                DB::raw('sum(t.fee) as total_fee'),'t.type',
                DB::raw("DATE_FORMAT(t.created_at,'%Y-%m') as date"),
                DB::raw('count(distinct t.id) as total')
            )
            ->groupBy(DB::raw("type,DATE_FORMAT(t.created_at,'%Y-%m')"))
            ->get();
//        dd($trails->toArray());

            $start_month = Carbon::parse(date("Y-m",strtotime($start_time)));
            $end_moth = Carbon::parse(date("Y-m",strtotime($end_time)));
            $diff = $end_moth->diffInMonths($start_month);//计算两个时间相差几个月

            $list = [];
            for ($i = 0;$i <= $diff;$i++){//获取两个时间之间
                $curr = $start_month->copy()->addMonth($i)->format('Y-m');
                foreach ($trails as $trail){//循环线索
                    if($trail->date == $curr){
                        $list[$curr][] = $trail;
                        $cloum = array_column($list[$curr],'type');
                        $sum_key = array_search('sum',$cloum);
                        if($sum_key === false){
                            $list[$curr][] = [
                                'total' => $trail->total,
                                "type"  =>  "sum"
                            ];
                        }else{
                            $list[$curr][$sum_key]['total'] +=  $trail->total;
                        }
                    }
                }
                if(empty($list[$curr])){
                    $list[$curr] = [];
                }
            }
        return
            [
                "sum"   =>  array_sum(array_column($trails->toArray(),'total')),
                "total_fee" =>  array_sum(array_column($trails->toArray(),'total_fee')),
                "trails"   =>  $list
            ];

    }

    /**
     * 销售线索占比
     * @param $start_time
     * @param $end_time
     * @param null $department  部门
     * @param null $target_star  目标艺人
     */
    public function percentageOfSalesLeads($start_time,$end_time,$department=null,$target_star=null)
    {
        $arr[] = ['t.created_at','>=',Carbon::parse($start_time)->toDateString()];
        $arr[]  =   ['t.created_at','<=',Carbon::parse($end_time)->toDateString()];
        if($department != null){
            $arr[] = ['du.department_id',$department];
        }
        if($target_star != null){
            $arr[] = ['ts.starable_id',$target_star];
        }
        $trails = (new Trail())->setTable("t")->from('trails as t')
            ->leftJoin('industries as i',"i.id",'=','t.industry_id')
            ->leftJoin('trail_star as ts',function ($join){
                $join->on('ts.trail_id','=','t.id')
                    ->where('ts.starable_type',ModuleableType::STAR)//艺人
                    ->where('ts.type',TrailStar::EXPECTATION);//目标
            })
            ->leftJoin('stars as s','ts.starable_id','=','s.id')
            ->leftJoin('module_users as mu',function ($join){
                $join->on('mu.moduleable_id','=','s.id')
                    ->where('mu.moduleable_type',ModuleableType::STAR)//艺人
                    ->where('mu.type',ModuleUserType::BROKER);//经纪人
            })
            ->leftJoin('users as u','u.id','=','mu.user_id')
            ->leftjoin('department_user as du','du.user_id','=','u.id')
            ->leftJoin('departments as d','d.id','=','du.department_id')
            ->whereIn('t.type',[Trail::TYPE_MOVIE,Trail::TYPE_VARIETY,Trail::TYPE_ENDORSEMENT])
            ->where($arr)
            ->select(DB::raw("distinct t.id"),"i.name as industry_name",'t.type',
                DB::raw("sum(t.fee) as total_fee"),
                DB::raw("DATE_FORMAT(t.created_at,'%Y-%m') as date"),
                DB::raw('count(t.id) as total'))
            ->groupBy(DB::raw("type,t.industry_id"))
            ->get();
        $sum = array_sum(array_column($trails->toArray(),'total'));
        foreach ($trails as &$trail){
            $trail['per'] = $sum == 0? 0 : floor(($trail['total'] / $sum)*10000)/10000;
        }
        return [
            "total" =>  $sum,
            'total_fee' =>  array_sum(array_column($trails->toArray(),'total_fee')),
            "trails"    =>  $trails
        ];
    }

    /**
     * 销售线索报表，行业分析
     * @param $start_time
     * @param $end_time
     * @param $type
     */
    public function industryAnalysis($start_time,$end_time,$type){
        $arr[] = ['t.created_at','>=',Carbon::parse($start_time)->toDateString()];
        $arr[]  =   ['t.created_at','<=',Carbon::parse($end_time)->toDateString()];
        if($type != null){
            $arr[] = ['d.id',$type];
        }
        (new Trail())->setTable("t")->from("trails as t")
            ->leftJoin('industries as i',"i.id",'=','t.industry_id')
            ->where($arr)
            ->whereIn('t.type',[Trail::TYPE_MOVIE,Trail::TYPE_VARIETY,Trail::TYPE_ENDORSEMENT])
            ->groupBy("t.industry_id")
            ->get([
                DB::raw('count(t.id) as total'),
                "i.name as industry_name"
            ]);
    }

    /*********************************************项目报表*****************************************************/
    public function projectReport($start_time,$end_time,$type,$department)
    {
        $arr[] = ['p.created_at','>=',Carbon::parse($start_time)->toDateString()];
        $arr[]  =   ['p.created_at','<=',Carbon::parse($end_time)->toDateString()];
        if($department != null){
            $arr[] = ['d.id',$department];
        }
        if($type != null){
            $arr[]  = ['p.type',$type];
        }
        $peroject_list = (new Project())->setTable("p")->from("projects as p")
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
            ->get([
                DB::raw('p.id'),
                DB::raw("GROUP_CONCAT(distinct d.name) as deparment_name"),
                DB::raw('sum(distinct co.contract_money) as total_contract_money'),
                DB::raw("GROUP_CONCAT(distinct s.name) as star_name"),
                'p.status','p.type','p.title',
                DB::raw('u.name as principal_name'),
                'p.trail_id'
            ]);
//        foreach ($peroject_list as &$project){
//            //查找部门
//            $department_list = (new TrailStar())->setTable("ts")->from("trail_star as ts")
//                ->where('ts.starable_type',ModuleableType::STAR)//艺人
//                ->where('ts.type',TrailStar::EXPECTATION)//目标
//                ->leftJoin('module_users as mu','mu.moduleable_id','=','ts.starable_id')
//                ->where('mu.moduleable_type',ModuleableType::STAR)//艺人
//                ->where('mu.type',ModuleUserType::BROKER)//经纪人
//                ->leftjoin('department_user as du','du.user_id','=','mu.user_id')
//                ->leftJoin('departments as d','d.id','=','du.department_id')
//                ->where('ts.trail_id',$project->trail_id)
//                ->get(['d.name']);
//            foreach ($department_list->toArray() as $deparment){
//                if(isset($deparment['name']) && $deparment['name'] != null)
//                    $project->deparment_name .= ",".$deparment['name'];
//            }
//            //查找艺人
//            $star_list = (new TrailStar())->setTable("ts")->from("trail_star as ts")
//                ->where('ts.starable_type',ModuleableType::STAR)//艺人
//                ->where('ts.type',TrailStar::EXPECTATION)//目标
//                ->leftJoin('stars as s','s.id','=','ts.starable_id')
//                ->where('ts.trail_id',$project->trail_id)
//                ->get(['s.id','s.name']);
//            foreach ($star_list->toArray() as $star){
//                if(isset($star['name']) && $star['name'] != null)
//                    $project->star_name .= ",".$star['name'];
//            }
//            $project->deparment_name = trim($project->deparment_name,",");
//            $project->star_name = trim($project->star_name,",");
//        }
        return [
            "sum"   =>  count($peroject_list),//数量合计
            "total_contract_amount" => array_sum(array_column($peroject_list->toArray(),'total_contract_money')), //合同总金额
            "total_project_cost"    =>  99999,//项目成本总额
            "project"   =>  $peroject_list
        ];
    }

    /**
     * 项目新增
     * @param $start_time
     * @param $end_time
     * @param null $department
     * @param null $target_star
     * @return mixed
     */
    public function newProject($start_time,$end_time,$department=null,$target_star=null)
    {
        $arr[] = ['p.created_at','>=',Carbon::parse($start_time)->toDateString()];
        $arr[]  =   ['p.created_at','<=',Carbon::parse($end_time)->toDateString()];
        if($department != null){
            $arr[] = ['d.id',$department];
        }
        if($target_star != null){
            $arr[] = ['s.id',$target_star];
        }
        $peoject_list = (new Project())->setTable("p")->from("projects as p")
            ->leftJoin('users as u','u.id','=','p.principal_id')
            ->leftJoin('trails as t','t.id','=','p.trail_id')
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
            ->leftJoin('departments as d','d.id','=','du.department_id')
            ->whereIn('t.type',[Trail::TYPE_MOVIE,Trail::TYPE_VARIETY,Trail::TYPE_ENDORSEMENT])
            ->whereIn('p.type',[Project::TYPE_VARIETY,Project::TYPE_ENDORSEMENT,Project::TYPE_MOVIE])
            ->where($arr)
            ->groupBy(DB::raw("p.type,DATE_FORMAT(p.created_at,'%Y-%m')"))
            ->get([
                DB::raw('count(distinct p.id) as total'),
                DB::raw("DATE_FORMAT(p.created_at,'%Y-%m') as date"),
                'p.type'
            ]);
        $start_month = Carbon::parse($start_time);
        $end_moth = Carbon::parse($end_time);
        $diff = $end_moth->diffInMonths($start_month);//计算两个时间相差几个月
        $list = [];
        for ($i = 0;$i <= $diff;$i++){
            $curr = $start_month->copy()->addMonth($i)->format('Y-m');
            foreach ($peoject_list as $project){
                if($project->date == $curr){
                    $list[$curr][] = $project;
                    $cloum = array_column($list[$curr],'type');
                    $sum_key = array_search('sum',$cloum);
                    if($sum_key === false){
                        $list[$curr][] = [
                            'total' => $project['total'],
                            "type"  =>  "sum"
                        ];
                    }else{
                        $list[$curr][$sum_key]['total'] +=  $project['total'];
                    }
                }
            }
            if(empty($list[$curr])){
                $list[$curr] = [];
            }
        }
        return [
            "sum"   => array_sum(array_column($peoject_list->toArray(),'total')),
            "data"  =>  $list
        ];
    }

    /**
     * 项目占比
     * @param $start_time
     * @param $end_time
     * @param null $department
     * @param null $target_star
     */
    public function percentageOfProject($start_time,$end_time,$department=null,$target_star=null)
    {
        $arr[] = ['p.created_at','>=',Carbon::parse($start_time)->toDateString()];
        $arr[]  =   ['p.created_at','<=',Carbon::parse($end_time)->toDateString()];
        if($department != null){
            $arr[] = ['d.id',$department];
        }
        if($target_star != null){
            $arr[] = ['ts.starable_id',$target_star];
        }
        $query = (new Project())->setTable("p")->from("projects as p")
            ->leftJoin('trails as t','t.id','=','p.trail_id')
            ->leftJoin('trail_star as ts',function ($join){
                $join->on('ts.trail_id','=','t.id')
                    ->where('ts.starable_type',ModuleableType::STAR)//艺人
                    ->where('ts.type',TrailStar::EXPECTATION);//目标
            })
            ->leftJoin('stars as s','s.id','=','ts.starable_id')
            ->leftJoin("module_users as mu",function ($join){
                $join->on('mu.moduleable_id','=','s.id')
                    ->where('mu.moduleable_type','=',ModuleableType::STAR)//艺人
                    ->where('mu.type','=',ModuleUserType::BROKER);//经纪人
            })
            ->leftJoin('users as u','u.id','=','mu.user_id')
            ->leftJoin('department_user as du','du.user_id','=','u.id')
            ->leftJoin('departments as d','d.id','=','du.department_id')
            ->leftJoin('template_field_values as tfv','tfv.project_id','=','p.id')
            ->where(function ($query){
                $query->where('s.sign_contract_status',SignContractStatus::ALREADY_SIGN_CONTRACT)
                    ->orWhere('s.sign_contract_status',SignContractStatus::ALREADY_TERMINATE_AGREEMENT);
            })->where($arr)
            ->whereIn('t.type',[Trail::TYPE_MOVIE,Trail::TYPE_VARIETY,Trail::TYPE_ENDORSEMENT]);

        $result1 = $query->where(function ($query){
            $query->where('p.type',Project::TYPE_MOVIE)//电影
            ->orWhere('p.type',Project::TYPE_VARIETY);//综艺
        })->where('tfv.field_id',7)//影视类型
        ->select(
            DB::raw('DISTINCT p.id as project_id'),
            DB::raw('count(DISTINCT p.id) as p_total'),
            DB::raw("case p.type when 1 then '影视项目' when 2 then '综艺项目' when 3 then '商业代言' else '数据错误' end type_name"),
            'p.type','tfv.value'
        )
            ->groupBy(DB::raw('p.type,tfv.value'))->get();

        $result2 = $query->where(function ($query){
            $query->where('p.type',Project::TYPE_ENDORSEMENT);//商务代言
        })->where('tfv.field_id',40)//电影商务
        ->select(
            DB::raw('DISTINCT p.id as project_id'),
            DB::raw('count(DISTINCT p.id) as p_total'),
            DB::raw("case p.type when 1 then '影视项目' when 2 then '综艺项目' when 3 then '商业代言' else '数据错误' end type_name"),
            'p.type','tfv.value'
        )
            ->groupBy(DB::raw('p.type,tfv.value'))->get();
        $result = array_merge($result1->toArray(),$result2->toArray());
        $list = [];
        $sum = array_sum(array_column($result,'p_total'));
        foreach ($result as $value) {
            unset($value['project_id']);
            $type_key = array_search($value['type'], array_column($list, 'type'));
            if ($type_key >= 0 && $type_key !== false) {
                $value['per_p_total'] = floor(($value['p_total'] / $sum) * 10000) / 10000;
                $list[$type_key]['type_total'] += $value['p_total'];
                $list[$type_key]['value'][] = $value;
                $list[$type_key]['per_type_total'] += $sum == 0 ? 0 : floor(($value['p_total'] / $sum) * 10000) / 10000;

            } else {
                $value['per_p_total'] = floor(($value['p_total'] / $sum) * 10000) / 10000;
                $list[] = [
                    'type_total' => $value['p_total'],
                    'per_type_total' => $sum == 0 ? 0 : floor(($value['p_total'] / $sum) * 10000) / 10000,
                    'type' => $value['type'],
                    'type_name' => $value['type_name'],
                    'value' => [$value]

                ];
            }
        }
//        foreach ($result as $value){
//            if(!isset($list[$value['type']])){
//                $list[$value['type']]['type_total'] = floor(($value['p_total'])*10000)/10000;
//                $list[$value['type']]['per_type_total'] = floor(($value['p_total'] / $sum)*10000)/10000;
//                $list[$value['type']]['type'] = $value['type'];
//                $value['per_p_total'] = floor(($value['p_total'] / $sum)*10000)/10000;
//                $list[$value['type']][] = $value;
//            }else{
//                $list[$value['type']]['type_total'] += $value['p_total'];
//                $value['per_p_total'] = floor(($value['p_total'] / $sum)*10000)/10000;
//                $list[$value['type']][] = $value;
//                $list[$value['type']]['per_type_total'] += floor(($value['p_total'] / $sum)*10000)/10000;
//            }
//
//        }

        return $list;

    }
    //客户报表
    public function clientReport($start_time,$end_time,$type=null)
    {
        $arr[] = ['c.created_at','>=',Carbon::parse($start_time)->toDateString()];
        $arr[]  =   ['c.created_at','<=',Carbon::parse($end_time)->toDateString()];
        if($type != null){
            $arr[] = ['c.type',$type];
        }
        $clients = (new Client())->setTable('c')->from('clients as c')
            ->leftJoin('users as u','u.id','=','c.principal_id')
            ->leftJoin('contacts as cs','cs.client_id','=','c.id')
            ->where($arr)
            ->where("cs.type",Contact::TYPE_KEY)
            ->groupBy('c.id')
//        $sql_with_bindings = str_replace_array('?', $clients->getBindings(), $clients->toSql());
//        dd($sql_with_bindings);
            ->get(['c.id','c.type','c.company','c.client_rating','c.grade','u.name as principal_name',
                DB::raw('GROUP_CONCAT(cs.name) as keyman'),
//                DB::raw('GROUP_CONCAT(cs.phone) as contact_phone'),
                DB::raw("case c.size when 1 then '上市公司' when 2 then '500强' end size"),
                DB::raw("case c.client_rating when 4 then 'S' when 3 then 'A' when 2 then 'B' when 1 then 'C' end client_rating")
                ]);
        return [
            "total"   =>  count($clients),
            "client"    =>  $clients
        ];
    }
    //客户分析
    public function clientAnalysis($start_time,$end_time)
    {
        $arr[] = ['created_at','>=',Carbon::parse($start_time)->toDateString()];
        $arr[]  =   ['created_at','<=',Carbon::parse($end_time)->toDateString()];
        $clients = Client::where($arr)
            ->whereIn('type',[Client::TYPE_MOVIE,Client::TYPE_VARIETY,Client::TYPE_ENDORSEMENT])
            ->groupBy(DB::raw("type,DATE_FORMAT(created_at,'%Y-%m')"))
            ->get([
                DB::raw("count(id) as total"),
                'type',
                DB::raw("DATE_FORMAT(created_at,'%Y-%m') as date")
            ]);
        $start_month = Carbon::parse($start_time);
        $end_moth = Carbon::parse($end_time);
        $diff = $end_moth->diffInMonths($start_month);//计算两个时间相差几个月
        $list = [];
        for ($i = 0;$i <= $diff;$i++){
            $curr = $start_month->copy()->addMonth($i)->format('Y-m');
            foreach ($clients as $client){
                if($client->date == $curr){
                    $list[$curr][] = $client;
                    $cloum = array_column($list[$curr],'type');
                    $sum_key = array_search('sum',$cloum);
                    if($sum_key === false){
                        $list[$curr][] = [
                            'total' => $client['total'],
                            "type"  =>  "sum"
                        ];
                    }else{
                        $list[$curr][$sum_key]['total'] +=  $client['total'];
                    }

                }
            }
            if(empty($list[$curr])){
                $list[$curr] = [];
            }
        }

        return [
            'total' =>  array_sum(array_column($clients->toArray(),'total')),
            "clients"   =>  $list
            ];
    }
    //签约中艺人报表Contract signing

    /**
     * 艺人报表
     * @param $start_time开始时间
     * @param $end_time结束时间
     * @param $sign_contract_status签约状态
     * @param $p_type 项目类型
     * @param $t_type 线索类型
     */
    public function starReport($start_time,$end_time,$sign_contract_status,$department=null,$target_star,$type=null)
    {
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
                ->select('s.sign_contract_status','s.name','s.birthday','s.source','s.communication_status','s.created_at','op.created_at as last_update_at')
//                            $sql_with_bindings = str_replace_array('?', $stars->getBindings(), $stars->toSql());
//                            dd($sql_with_bindings);
                ->get();
        }else{//已签约/解约
//            $contract = (new Star())->get(['id']);
//            $co = Contract::where('star_type','stars')->get();
//           foreach($contract as $key => $val){
//               $val
//           }

            //合同，预计订单收入，花费金额都没查呢
            $stars = (new Star())->setTable("s")->from("stars as s")
                ->leftJoin("module_users as mu",function ($join){
                    $join->on('mu.moduleable_id','=','s.id')
                        ->where('mu.moduleable_type','=',ModuleableType::STAR)//艺人
                        ->where('mu.type','=',ModuleUserType::BROKER);//经纪人
                })->leftJoin("department_user as du",'du.user_id','=','mu.user_id')
                ->leftJoin('departments as d','d.id','=','du.department_id')
                ->leftJoin("trail_star as ts",function ($join){
                    $join->on('ts.starable_id','=','s.id')
                        ->where('ts.starable_type','=',ModuleableType::STAR)//艺人
                        ->where('ts.type',TrailStar::EXPECTATION);//目标
                })
                ->leftJoin("contracts as co",function ($join){
               //     $join->on('co.stars','like','s.id')//艺人
               //     $join->on('co.stars','<', '(LENGTH(s.id)-LENGTH(REPLACE(s.id,\',\',\'\'))+1) ')
                    $join->whereRaw("FIND_IN_SET(s.id,stars)")
                    ->where('co.star_type','=','stars');
                })
                ->leftJoin('trails as t','t.id','=','ts.trail_id')
                ->leftJoin('projects as p','p.trail_id','=','ts.trail_id')
                ->where($arr)
                ->groupBy('s.id')

//                               $sql_with_bindings = str_replace_array('?', $stars->getBindings(), $stars->toSql());
//        dd($sql_with_bindings);
                ->get([
                    's.id','s.name','sign_contract_status',
                    DB::raw('sum(distinct t.fee) as total_fee'),
                    DB::raw('sum(distinct co.contract_money) as total_contract_money'),
           //         DB::raw('SUBSTRING_INDEX(SUBSTRING_INDEX(leave_entries.dates, \',\', numbers.n), \',\', -1)  as total_contract_money'),
                    DB::raw("count(distinct ts.id) as trail_total"),
                    DB::raw("count(distinct p.id) as project_total"),
                    DB::raw("GROUP_CONCAT(DISTINCT d.name) as department_name")
                ]);

        }
        return [
            "total" =>  count($stars),
            "total_fee" => array_sum(array_column($stars->toArray(),'total_fee')),
            "total_contract_amount" => array_sum(array_column($stars->toArray(),'total_contract_money')), //合同总金额
            "total_expenditure" => '',   //花费金额

            "stars" =>  $stars
        ];

    }

    /**
     * 艺人分析--线索
     * @param $start_time
     * @param $end_time
     * @param $deparment
     * @param $target_star
     */
    public function starTrailAnalysis($start_time,$end_time,$department,$target_star)
    {
        $arr[] = ['s.created_at','>=',Carbon::parse($start_time)->toDateString()];
        $arr[]  =   ['s.created_at','<=',Carbon::parse($end_time)->toDateString()];
        if($department != null){
            $arr[] = ['d.id',$department];
        }
        if($target_star != null){
            $arr[] = ['s.id',$target_star];
        }
        $query = (new Star())->setTable("s")->from("stars as s")
            ->leftJoin("trail_star as ts",function ($join){
                $join->on('ts.starable_id','=','s.id')
                    ->where('ts.starable_type','=',ModuleableType::STAR)//艺人
                    ->where('ts.type',TrailStar::EXPECTATION);//目标
            })->leftJoin("trails as t",'t.id','=',"ts.trail_id")
            ->leftJoin('module_users as mu',function ($join){
                $join->on('mu.moduleable_id','=','s.id')
                    ->where('mu.moduleable_type','=',ModuleableType::STAR)//艺人
                    ->where('mu.type','=',ModuleUserType::BROKER);//经纪人
            })
            ->leftJoin('users as u','u.id','=','mu.user_id')
            ->leftJoin('department_user as du','du.user_id','=','u.id')
            ->leftJoin('departments as d','d.id','=','du.department_id')
            ->leftJoin('projects as p','p.trail_id','=','t.id')
            ->leftJoin('template_field_values as tfv','tfv.project_id','=','p.id')
            ->groupBy('t.type','t.industry_id')
            ->whereIn('t.type',[Trail::TYPE_MOVIE,Trail::TYPE_VARIETY,Trail::TYPE_ENDORSEMENT])
            ->where($arr);

//            ->whereRaw('t.id is not null')

//            ->select(DB::raw('DISTINCT t.id,count(DISTINCT t.id) as total,t.type,t.industry_id'))
//            ->get();

        $result1 = $query->where(function ($query){
            $query->where('p.type',Project::TYPE_MOVIE)//电影
            ->orWhere('p.type',Project::TYPE_VARIETY);//综艺
        })->where('tfv.field_id',7)//影视类型
        ->select(DB::raw('DISTINCT p.id as project_id'),DB::raw('count(DISTINCT p.id) as total'),'p.type','tfv.value')
            ->groupBy(DB::raw('p.type,tfv.value'))->get()->toArray();

        $result2 = $query->where(function ($query){
            $query->where('p.type',Project::TYPE_ENDORSEMENT);//商务代言
        })->where('tfv.field_id',40)//电影商务
        ->select(DB::raw('DISTINCT p.id as project_id'),DB::raw('count(DISTINCT p.id) as total'),'p.type','tfv.value')
            ->groupBy(DB::raw('p.type,tfv.value'))->get()->toArray();

        $result = array_merge($result1,$result2);
        $list = [];
        $sum = array_sum(array_column($result,'total'));
        foreach ($result as $value){
            if(!isset($list[$value['type']])){
                $list[$value['type']]['type'] = $value['type'];
                $list[$value['type']]['total'] = $value['total'];
                $list[$value['type']]['per_type'] = $value['total'] / $sum;
                $value['per_industry'] = $value['total'] / $sum;
                $list[$value['type']][] = $value;
            }else{
                $list[$value['type']]['total'] += $value['total'];
                $list[$value['type']]['per_type'] += $value['total'] / $sum;
                $value['per_industry'] = $value['total'] / $sum;
                $list[$value['type']][] = $value;
            }
        }
        return $list;

    }

    /**
     * 艺人分析项目
     * @param $start_time
     * @param $end_time
     * @param $deparment
     * @param $target_star
     */
    public function starProjectAnalysis($start_time,$end_time,$department,$target_star)
    {
        $arr[] = ['s.created_at','>=',Carbon::parse($start_time)->toDateString()];
        $arr[]  =   ['s.created_at','<=',Carbon::parse($end_time)->toDateString()];
        if($department != null){
            $arr[] = ['d.id',$department];
        }
        if($target_star != null){
            $arr[] = ['s.id',$target_star];
        }
        //查询，查询艺人已签约和已解约的
        $query = (new Star())->setTable("s")->from("stars as s")
            ->leftJoin('trail_star as ts',function ($join) {
                $join->on('ts.starable_id','=','s.id')
                ->where('ts.starable_type',ModuleableType::STAR)
                ->where('ts.type',TrailStar::EXPECTATION);
            })
            ->leftJoin('trails as t','t.id','=','ts.trail_id')
            ->leftJoin('projects as p','p.trail_id','=','t.id')
            ->leftJoin('module_users as mu',function ($join){
                $join->on('mu.moduleable_id','=','s.id')
                    ->where('mu.moduleable_type','=',ModuleableType::STAR)//艺人
                    ->where('mu.type','=',ModuleUserType::BROKER);//经纪人
            })
            ->leftJoin('users as u','u.id','=','mu.user_id')
            ->leftJoin('department_user as du','du.user_id','=','u.id')
            ->leftJoin('departments as d','d.id','=','du.department_id')
            ->where(function ($query){
                $query->where('s.sign_contract_status',SignContractStatus::ALREADY_SIGN_CONTRACT)
                    ->orWhere('s.sign_contract_status',SignContractStatus::ALREADY_TERMINATE_AGREEMENT);
            })
            ->where($arr)
            ->leftJoin('template_field_values as tfv','tfv.project_id','=','p.id');
        $result1 = $query->where(function ($query){
            $query->where('p.type',Project::TYPE_MOVIE)//电影
                ->orWhere('p.type',Project::TYPE_VARIETY);//综艺
        })->where('tfv.field_id',7)//影视类型
            ->select(
                    DB::raw("count(distinct s.id) as star_total"),
                    DB::raw("sum(t.fee) as fee_sum"),
//                    DB::raw('DISTINCT p.id as project_id'),
                    DB::raw('count(DISTINCT p.id) as p_total'),
                    DB::raw("case p.type when 1 then '影视项目' when 2 then '综艺项目' when 3 then '商业代言' else '数据错误' end type_name"),
                    'p.type','tfv.value')
            ->groupBy(DB::raw('p.type,tfv.value'))->get();

        $result2 = $query->where(function ($query){
            $query->where('p.type',Project::TYPE_ENDORSEMENT);//商务代言
        })->where('tfv.field_id',40)//电影商务
            ->select(
                    DB::raw("count(distinct s.id) as star_total"),
                    DB::raw("sum(t.fee) as fee_sum"),
//                    DB::raw('DISTINCT p.id as project_id'),
                    DB::raw('count(DISTINCT p.id) as p_total'),
                    DB::raw("case p.type when 1 then '影视项目' when 2 then '综艺项目' when 3 then '商业代言' else '数据错误' end type_name"),
                    'p.type','tfv.value')
            ->groupBy(DB::raw('p.type,tfv.value'))->get();
        $result = array_merge($result1->toArray(),$result2->toArray());
        $list = [];
        $sum = array_sum(array_column($result,'p_total'));
        foreach ($result as $value){
            unset($value['project_id']);
            $type_key = array_search($value['type'],array_column($list,'type'));
            if($type_key >= 0 && $type_key !== false){
                $value['per_p_total'] = floor(($value['p_total'] / $sum)*10000)/10000;
                $list[$type_key]['type_total']  +=  $value['p_total'];
                $list[$type_key]['value'][]   =   $value;
                $list[$type_key]['per_type_total']  += $sum == 0 ? 0 : floor(($value['p_total'] / $sum)*10000)/10000;

            }else{
                $value['per_p_total'] = floor(($value['p_total'] / $sum)*10000)/10000;
                $list[] = [
                    'type_total'    =>  $value['p_total'],
                    'per_type_total'    =>  $sum == 0 ? 0 : floor(($value['p_total'] / $sum)*10000)/10000,
                    'type'  =>  $value['type'],
                    'type_name' =>  $value['type_name'],
                    'value' =>  [$value]

                ];
            }
//            if(!isset($list[$value['type']])){
//
//                $list[$value['type']]['type_total'] = $value['p_total'];
//                $list[$value['type']]['per_type_total'] = $value['p_total'] / $sum;
//                $list[$value['type']]['type'] = $value['type'];
//                $value['per_p_total'] = $value['p_total'] / $sum;
//                $list[$value['type']][] = $value;
//            }else{
//                $list[$value['type']]['type_total'] += $value['p_total'];
//                $value['per_p_total'] = $value['p_total'] / $sum;
//                $list[$value['type']][] = $value;
//                $list[$value['type']]['per_type_total'] += $value['p_total'] / $sum;
//            }

        }

        return [
            "star_total"    =>  array_sum(array_column($result,'star_total')),
            'total_fee' =>  array_sum(array_column($result,'fee_sum')),
            "list"  =>  $list
            ];
    }

    /**
     * 博主报表
     * @param $start_time
     * @param $end_time
     * @param $sign_contract_status
     */
    public function bloggerReport($start_time,$end_time,$sign_contract_status,$department,$target_star)
    {
        $arr[] = ['b.created_at','>=',Carbon::parse($start_time)->toDateString()];
        $arr[]  =   ['b.created_at','<=',Carbon::parse($end_time)->toDateString()];
        $arr[] = ['b.sign_contract_status',$sign_contract_status];
        if($department != null){
            $arr[] = ['d.id',$department];
        }
        if($target_star != null){
            $arr[] = ['s.id',$target_star];
        }
        //签约中
        if($sign_contract_status == SignContractStatus::SIGN_CONTRACTING){
            $sub_query = DB::table("operate_logs")->groupBy("created_at")->select(DB::raw("max(created_at) as created_at,id,logable_id,logable_type,method"));
            $bloggers = (new Blogger())->setTable('b')->from('bloggers as b')
                ->leftJoin(DB::raw("({$sub_query->toSql()}) as op"),function ($join){
                    $join->on('op.logable_id','=','b.id')
                        ->where('op.logable_type','=',ModuleableType::BLOGGER)//可能有问题
                     //   ->where('op.method','=',OperateEntity::UPDATED_AT);
                    ->where('op.method','=',OperateLogMethod::FOLLOW_UP);
                })->leftJoin("blogger_types as bt","bt.id","b.type_id")
                ->where($arr)
                ->groupBy('b.id')
                ->select('b.nickname','bt.name as type_id','b.communication_status','b.created_at','op.created_at as last_update_at')
                ->get();
//
//            $sql_with_bindings = str_replace_array('?', $bloggers->getBindings(), $bloggers->toSql());
//        dd($sql_with_bindings);
        }else{
            //合同，预计订单收入，花费金额都没查呢
            $bloggers = (new Blogger())->setTable("b")->from("bloggers as b")
                ->leftJoin("module_users as mu",function ($join){
                    $join->on('mu.moduleable_id','=','b.id')
                        // 从 star 修改成  blogger    张
                        ->where('mu.moduleable_type','=',ModuleableType::BLOGGER)//艺人
                        // 从 star 修改成  blogger    张
                        ->where('mu.type','=',ModuleUserType::PRODUCER);//制作人
                })->leftJoin("department_user as du",'du.user_id','=','mu.user_id')
                ->leftJoin('departments as d','d.id','=','du.department_id')
                ->leftJoin("contracts as co",function ($join){
                    $join->whereRaw("FIND_IN_SET(b.id,stars)")
                        ->where('co.star_type','=','bloggers');
                })
                ->leftJoin("trail_star as ts",function ($join){
                    $join->on('ts.starable_id','=','b.id')
                        ->where('ts.starable_type','=',ModuleableType::BLOGGER)//艺人
                        ->where('ts.type',TrailStar::EXPECTATION);//目标
                })->leftJoin('trails as t','t.id','=','ts.trail_id')
                ->leftJoin('projects as p','p.trail_id','=','t.id')
                ->where($arr)
                ->groupBy('b.id')
//                       $sql_with_bindings = str_replace_array('?', $bloggers->getBindings(), $bloggers->toSql());
//        dd($sql_with_bindings);
//
                ->get([
                    'b.id','b.nickname','t.fee','sign_contract_status',
                    // 少了合同金额    花费金额
                    DB::raw('sum(distinct t.fee) as total_fee'),
                    DB::raw('sum(distinct co.contract_money) as total_contract_money'),
                    DB::raw("count(ts.id) as trail_total"),
                    DB::raw("count(p.id) as project_total"),
                    DB::raw("GROUP_CONCAT(DISTINCT d.name) as department_name")
                ]);
        }
        return [
            "total" =>  count($bloggers),
            "total_fee" => array_sum(array_column($bloggers->toArray(),'total_fee')),
            "total_contract_money" => array_sum(array_column($bloggers->toArray(),'total_contract_money')), //合同总金额
            "total_expenditure_money" => '',   //花费金额
            "blogger" =>  $bloggers
        ];
    }

    /**
     * 博主分析--线索
     * @param $start_time
     * @param $end_time
     * @param $deparment
     * @param $target_star
     */
    public function bloggerTrailAnalysis($start_time,$end_time,$department,$target_star)
    {
        $arr[] = ['b.created_at','>=',Carbon::parse($start_time)->toDateString()];
        $arr[]  =   ['b.created_at','<=',Carbon::parse($end_time)->toDateString()];
        if($department != null){
            $arr[] = ['d.id',$department];
        }
        if($target_star != null){
            $arr[] = ['b.id',$target_star];
        }
        /**
         * SELECT DISTINCT t.id,s.id  as star_id,count(t.type),count(t.industry_id),t.id as trail_id,t.type,t.industry_id from stars as s
         * LEFT JOIN trail_star as ts on s.id = ts.starable_id and ts.starable_type='star' and ts.type = 1
         * LEFT JOIN trails as t on t.id = ts.trail_id
         * where t.id is not null
         * GROUP BY t.type,t.industry_id
         */
        $result = (new Star())->setTable("b")->from("bloggers as b")
            ->leftJoin("trail_star as ts",function ($join){
                $join->on('ts.starable_id','=','b.id')
                    ->where('ts.starable_type','=',ModuleableType::BLOGGER)//艺人
                    ->where('ts.type',TrailStar::EXPECTATION);//目标
            })->leftJoin("trails as t",'t.id','=',"ts.trail_id")
            ->leftJoin('module_users as mu',function ($join){
                $join->on('mu.moduleable_id','=','b.id')
                    ->where('mu.moduleable_type','=',ModuleableType::STAR)//艺人
                    ->where('mu.type','=',ModuleUserType::BROKER);//经纪人
            })
            ->leftJoin('users as u','u.id','=','mu.user_id')
            ->leftJoin('department_user as du','du.user_id','=','u.id')
            ->leftJoin('departments as d','d.id','=','du.department_id')
            ->where($arr)
            ->whereIn('t.type',[Trail::TYPE_MOVIE,Trail::TYPE_VARIETY,Trail::TYPE_ENDORSEMENT])
            ->where('b.sign_contract_status','=',SignContractStatus::ALREADY_TERMINATE_AGREEMENT)
            ->whereOr('b.sign_contract_status','=',SignContractStatus::ALREADY_SIGN_CONTRACT)
            ->whereRaw('t.id is not null')
            ->groupBy('t.id')
            ->select(DB::raw('DISTINCT t.id,count(distinct t.id) as total,t.type,t.industry_id'))
            ->get();
        $list = [];
        $sum = array_sum(array_column($result->toArray(),'total'));
        foreach ($result as $value){
            if(!isset($list[$value['type']])){
                $list[$value['type']]['type'] = $value['type'];
                $list[$value['type']]['total'] = $value['total'];
                $list[$value['type']]['per_type'] = $value['total'] / $sum;
                $value['per_industry'] = $value['total'] / $sum;
                $list[$value['type']][] = $value;
            }else{
                $list[$value['type']]['total'] += $value['total'];
                $list[$value['type']]['per_type'] += $value['total'] / $sum;
                $value['per_industry'] = $value['total'] / $sum;
                $list[$value['type']][] = $value;
            }
        }
        return $list;
    }

    /**
     * @param $start_time
     * @param $end_time
     * @param $deparment
     * @param $target_star
     * @return array
     */
    public function bloggerProjectAnalysis($start_time,$end_time,$department,$target_star)
    {
        $arr[] = ['b.created_at','>=',Carbon::parse($start_time)->toDateString()];
        $arr[]  =   ['b.created_at','<=',Carbon::parse($end_time)->toDateString()];
        if($department != null){
            $arr[] = ['d.id',$department];
        }
        if($target_star != null){
            $arr[] = ['b.id',$target_star];
        }
        $query = (new Blogger())->setTable("b")->from("bloggers as b")
            ->leftJoin('trail_star as ts',function ($join) {
                $join->on('ts.starable_id','=','b.id')
                    ->where('ts.starable_type',ModuleableType::STAR)
                    ->where('ts.type',TrailStar::EXPECTATION);
            })
            ->leftJoin('projects as p','p.trail_id','=','ts.trail_id')
            ->leftJoin('module_users as mu',function ($join){
                $join->on('mu.moduleable_id','=','b.id')
                    ->where('mu.moduleable_type','=',ModuleableType::STAR)//艺人
                    ->where('mu.type','=',ModuleUserType::BROKER);//经纪人
            })
            ->leftJoin('users as u','u.id','=','mu.user_id')
            ->leftJoin('department_user as du','du.user_id','=','u.id')
            ->leftJoin('departments as d','d.id','=','du.department_id')
            ->where($arr)
            ->where(function ($query){
                $query->where('b.sign_contract_status',SignContractStatus::ALREADY_SIGN_CONTRACT)
                    ->orWhere('b.sign_contract_status',SignContractStatus::ALREADY_TERMINATE_AGREEMENT);
            })->leftJoin('template_field_values as tfv','tfv.project_id','=','p.id');

        $result1 = $query->where(function ($query){
            $query->where('p.type',Project::TYPE_MOVIE)//电影
            ->orWhere('p.type',Project::TYPE_VARIETY);//综艺
        })->where('tfv.field_id',7)//影视类型
        ->select(DB::raw('DISTINCT p.id as project_id'),DB::raw('count(DISTINCT p.id) as p_total'),'p.type','tfv.value')
            ->groupBy(DB::raw('p.type,tfv.value'))->get();

        $result2 = $query->where(function ($query){
            $query->where('p.type',Project::TYPE_ENDORSEMENT);//商务代言
        })->where('tfv.field_id',40)//电影商务
        ->select(DB::raw('DISTINCT p.id as project_id'),DB::raw('count(DISTINCT p.id) as p_total'),'p.type','tfv.value')
            ->groupBy(DB::raw('p.type,tfv.value'))->get();
        $result = array_merge($result1->toArray(),$result2->toArray());
        $list = [];
        $sum = array_sum(array_column($result,'p_total'));
        foreach ($result as $value){
            if(!isset($list[$value['type']])){
                $list[$value['type']]['type_total'] = $value['p_total'];
                $list[$value['type']]['per_type_total'] = $value['p_total'] / $sum;
                $list[$value['type']]['type'] = $value['type'];
                $value['per_p_total'] = $value['p_total'] / $sum;
                $list[$value['type']][] = $value;
            }else{
                $list[$value['type']]['type_total'] += $value['p_total'];
                $value['per_p_total'] = $value['p_total'] / $sum;
                $list[$value['type']][] = $value;
                $list[$value['type']]['per_type_total'] += $value['p_total'] / $sum;
            }

        }

        return $list;
    }


}
