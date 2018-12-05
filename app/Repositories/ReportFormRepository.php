<?php

namespace App\Repositories;

use App\Models\Client;
use App\Models\Project;
use App\Models\Star;
use App\Models\Trail;
use App\Models\TrailStar;
use App\Models\User;
use App\ModuleableType;
use App\ModuleUserType;
use App\SignContractStatus;
use Carbon\Carbon;
use DemeterChain\C;
use Illuminate\Support\Facades\DB;

class ReportFormRepository
{
    public function CommercialFunnelReportFrom($start_time,$end_time)
    {
        $start_time = Carbon::parse($start_time)->toDateString();
        $end_time = Carbon::parse($end_time)->toDateString();
        $current_industry_trail_number = $this->getEveryIndustryTrailConfrimNumber($start_time,$end_time);
        $current_industry_trail_confirm_number = $this->getEveryIndustryTrailConfrimNumber($start_time,$end_time,Trail::STATUS_CONFIRMED);

        //获取查询周期
        $rang = Carbon::parse($start_time)->diffInDays($end_time);
        $prev_range_end = Carbon::parse($end_time)->addDay(-($rang+1))->toDateString();
        $prev_range_start = Carbon::parse($start_time)->addDay(-($rang+1))->toDateString();

        $prev_industry_trail_number = $this->getEveryIndustryTrailConfrimNumber($prev_range_start,$prev_range_end);
        $prev_industry_trail_confirm_number = $this->getEveryIndustryTrailConfrimNumber($prev_range_start,$prev_range_end,Trail::STATUS_CONFIRMED);

        $prev_year_start = Carbon::parse($start_time)->addYear(-1)->toDateString();
        $prev_year_end = Carbon::parse($end_time)->addYear(-1)->toDateString();
        $prev_year_industry_trail_number = $this->getEveryIndustryTrailConfrimNumber($prev_year_start,$prev_year_end);
        $prev_year_industry_trail_confirm_number = $this->getEveryIndustryTrailConfrimNumber($prev_year_start,$prev_year_end,Trail::STATUS_CONFIRMED);

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
            "industry"  =>  $industry_data,
            "cooperation"   =>  $cooperation_data,
            "resource"  =>  $resource_type_data,
            'priority'  =>  $priority_data
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
//        dd($current_industry_trail_number);
        //计算数量占比,计算同比
        array_map(function ($v) use ($sum,$prev,$prev_year){
            $v->ratio = $sum == 0 ? 0 : ($v->number)/$sum; //数量占比

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
        //合并两个数组
        array_map(function ($v) use ($curr_confirm){
            $current_confirm_Arr = $curr_confirm->toArray();
            $key = array_search($v->id,array_column($current_confirm_Arr,'id'));
            $v->confirm_ratio_increment   =   $current_confirm_Arr[$key]->confirm_ratio_increment;
            $v->confirm_annual_increment  =   $current_confirm_Arr[$key]->confirm_annual_increment;
            $v->customer_conversion_rate = $v->number == 0? 0 :$current_confirm_Arr[$key]->number / $v->number;
            $v->confirm_number = $current_confirm_Arr[$key]->number;
            return $v;
        },$curr->toArray());
        return $curr;
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
        $sub_query = "SELECT (@num := @num + 1) as id from trails,(SELECT @num := 0) t1 limit 8";
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
        //DB::connection()->enableQueryLog();

        $subquery = DB::table("trails as t")
            ->where($arr)
            ->select("t.industry_id","t.id");

        return DB::table(DB::raw("({$subquery->toSql()}) as tt"))
            ->rightJoin("industries as i",'i.id','=','tt.industry_id')
            ->mergeBindings($subquery)
            ->groupBy("i.id")
            ->get([
                DB::raw("count(tt.id) as number"),"i.id","i.name"
            ]);

    }

    //商务漏斗，分析留存率
    public function salesFunnel($start_time,$end_time)
    {
        //根据状态分组统计销售线索个状态下
        $staus_trail_number = Trail::select("status",DB::raw("count(id) as total"))
            ->where('created_at','>',Carbon::parse($start_time)->toDateString())
            ->where('created_at','<',Carbon::parse($end_time)->toDateString())
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
        $refuse_retention = $sum == 0 ? 0 : $retention_trail_number / $sum;
        //客户拒绝后的留存率
        $retention_trail_number = $sum - (
            (isset($status_number[Trail::PROGRESS_REFUSE]) ? + $status_number[Trail::PROGRESS_REFUSE] : 0) +
            (isset($status_number[Trail::PROGRESS_CANCEL])?$status_number[Trail::PROGRESS_CANCEL] : 0)
            );
        $client_refuse_retention = $sum == 0 ? 0 : $retention_trail_number / $sum;

        //进入谈判留存率
        $retention_trail_number = $sum - (
                (isset($status_number[Trail::PROGRESS_REFUSE]) ? + $status_number[Trail::PROGRESS_REFUSE] : 0) +
                (isset($status_number[Trail::PROGRESS_CANCEL])?$status_number[Trail::PROGRESS_CANCEL] : 0) +
                (isset($status_number[Trail::PROGRESS_TALK])?$status_number[Trail::PROGRESS_TALK] : 0)
            );
        $talk_retention = $sum == 0 ? 0 : $retention_trail_number / $sum;

        //意向签约留存率 intention
        $retention_trail_number = $sum - (
                (isset($status_number[Trail::PROGRESS_REFUSE]) ? + $status_number[Trail::PROGRESS_REFUSE] : 0) +
                (isset($status_number[Trail::PROGRESS_CANCEL])?$status_number[Trail::PROGRESS_CANCEL] : 0) +
                (isset($status_number[Trail::PROGRESS_TALK])?$status_number[Trail::PROGRESS_TALK] : 0)+
                (isset($status_number[Trail::PROGRESS_INTENTION])?$status_number[Trail::PROGRESS_INTENTION]:0)
            );
        $intention_retention = $sum == 0 ? 0 : $retention_trail_number / $sum;

        //签约完成留存率
        $retention_trail_number = $sum - (
                (isset($status_number[Trail::PROGRESS_REFUSE]) ? + $status_number[Trail::PROGRESS_REFUSE] : 0) +
                (isset($status_number[Trail::PROGRESS_CANCEL])?$status_number[Trail::PROGRESS_CANCEL] : 0) +
                (isset($status_number[Trail::PROGRESS_TALK])?$status_number[Trail::PROGRESS_TALK] : 0)+
                (isset($status_number[Trail::PROGRESS_INTENTION])?$status_number[Trail::PROGRESS_INTENTION]:0)+
                (isset($status_number[Trail::PROGRESS_SIGNING]) ? $status_number[Trail::PROGRESS_SIGNING] : 0)+
                (isset($status_number[Trail::PROGRESS_SIGNED]) ? $status_number[Trail::PROGRESS_SIGNED] : 0)
            );
        $signed_retention = $sum == 0 ? 0 : $retention_trail_number / $sum;
        //项目结算留存率
        return [
            "touch_total"   =>  $sum,//接触总量
            'refuse_retention'  =>  $refuse_retention,//主动拒绝
            'client_refuse_retention'   =>  $client_refuse_retention,//客户拒绝
            'talk_retention'    =>  $talk_retention,//谈判留存
            'intention_retention'   =>  $intention_retention,//意向签约
            //项目结算
            //归档
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
        $arr[] = ['t.created_at','>',Carbon::parse($start_time)->toDateString()];
        $arr[]  =   ['t.created_at','<',Carbon::parse($end_time)->toDateString()];
        if($type != null){
            $arr[]  = ['t.type',$type];
        }
        if($department != null){
            $arr[] = ['du.department_id',$department];
        }
        $trails = (new Trail())->setTable('t')->from('trails as t')
            ->select(DB::raw('distinct t.id'),"t.type",'t.title','t.resource_type','t.fee','t.status','t.priority',DB::raw('u.name as principal_user'))
            ->leftJoin('trail_star as ts','ts.trail_id','=','t.id')
            ->where('ts.starable_type',ModuleableType::STAR)//艺人
            ->where('ts.type',TrailStar::EXPECTATION)//目标
//            ->leftJoin('stars as s','s.id','=','ts.starable_id')
            ->leftJoin('module_users as mu','mu.moduleable_id','=','ts.starable_id')
            ->where('mu.moduleable_type',ModuleableType::STAR)//艺人
            ->where('mu.type',ModuleUserType::BROKER)//经纪人
            ->leftjoin('department_user as du','du.user_id','=','mu.user_id')
//            ->leftjoin('departments as d','d.id','=','department_id')
            ->leftJoin('users as u','u.id','=','t.principal_id')
            ->where($arr)->get();
        foreach ($trails as &$trail){
            //获取线索对应的部门
            $department_list = (new TrailStar())->setTable("ts")->from("trail_star as ts")
                ->leftJoin("module_users as mu",'mu.moduleable_id','=','ts.starable_id')
                ->where('ts.starable_type',ModuleableType::STAR)//艺人
                ->where('ts.type',TrailStar::EXPECTATION)//目标
                ->leftjoin('department_user as du','du.user_id','=','mu.user_id')
                ->leftjoin('departments as d','d.id','=','department_id')
                ->where('ts.trail_id',$trail->id)
                ->get(['d.name']);
            foreach ($department_list->toArray() as $deparment){
                if(isset($deparment['name']) && $deparment['name'] != null)
                    $trail->deparment_name .= ",".$deparment['name'];
            }
            //获取线索对应的目标艺人
            $star_list = (new TrailStar())->setTable("ts")->from("trail_star as ts")
                ->leftJoin("stars as s",'s.id','=','ts.starable_id')
                ->where('ts.trail_id',$trail->id)
                ->where('ts.starable_type',ModuleableType::STAR)
                ->where('ts.type',TrailStar::EXPECTATION)//目标
                ->get(['s.name']);
            foreach ($star_list->toArray() as $star){
                if(isset($star['name']) && $star['name'] != null)
                $trail->star_name .= ",".$star['name'];
            }
            $trail->deparment_name = trim($trail->deparment_name,",");
            $trail->star_name .= trim($trail->star_name,",");
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
        $arr[] = ['t.created_at','>',Carbon::parse($start_time)->toDateString()];
        $arr[]  =   ['t.created_at','<',Carbon::parse($end_time)->toDateString()];
        if($department != null){
            $arr[] = ['du.department_id',$department];
        }
        if($target_star != null){
            $arr[] = ['ts.starable_id',$target_star];
        }
        $trails = (new Trail())->setTable("t")->from('trails as t')
            ->leftJoin('trail_star as ts','ts.trail_id','=','t.id')
            ->where('ts.starable_type',ModuleableType::STAR)//艺人
            ->where('ts.type',TrailStar::EXPECTATION)//目标
            ->leftJoin('module_users as mu','mu.moduleable_id','=','ts.starable_id')
            ->where('mu.moduleable_type',ModuleableType::STAR)//艺人
            ->where('mu.type',ModuleUserType::BROKER)//经纪人
            ->leftjoin('department_user as du','du.user_id','=','mu.user_id')
            ->where($arr)
            ->select(DB::raw("distinct t.id"),'t.type',DB::raw("DATE_FORMAT(t.created_at,'%Y-%m') as date"),DB::raw('count(t.id) as total'))
            ->groupBy(DB::raw("type,DATE_FORMAT(t.created_at,'%Y-%m')"))
            ->get();
        return $trails;

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
        $arr[] = ['t.created_at','>',Carbon::parse($start_time)->toDateString()];
        $arr[]  =   ['t.created_at','<',Carbon::parse($end_time)->toDateString()];
        if($department != null){
            $arr[] = ['du.department_id',$department];
        }
        if($target_star != null){
            $arr[] = ['ts.starable_id',$target_star];
        }
        $trails = (new Trail())->setTable("t")->from('trails as t')
            ->leftJoin('industries as i',"i.id",'=','t.industry_id')
            ->leftJoin('trail_star as ts','ts.trail_id','=','t.id')
            ->where('ts.starable_type',ModuleableType::STAR)//艺人
            ->where('ts.type',TrailStar::EXPECTATION)//目标
            ->leftJoin('module_users as mu','mu.moduleable_id','=','ts.starable_id')
            ->where('mu.moduleable_type',ModuleableType::STAR)//艺人
            ->where('mu.type',ModuleUserType::BROKER)//经纪人
            ->leftjoin('department_user as du','du.user_id','=','mu.user_id')
            ->where($arr)
            ->select(DB::raw("distinct t.id"),"i.name as industry_name",'t.type',DB::raw("DATE_FORMAT(t.created_at,'%Y-%m') as date"),DB::raw('count(t.id) as total'))
            ->groupBy(DB::raw("type,t.industry_id"))
            ->get();
        $sum = array_sum(array_column($trails->toArray(),'total'));
        foreach ($trails as &$trail){
            $trail['per'] = $sum == 0? 0 : $trail['total'] / $sum;
        }
        return $trails;
    }

    /**
     * 销售线索报表，行业分析
     * @param $start_time
     * @param $end_time
     * @param $type
     */
    public function industryAnalysis($start_time,$end_time,$type){
        $arr[] = ['t.created_at','>',Carbon::parse($start_time)->toDateString()];
        $arr[]  =   ['t.created_at','<',Carbon::parse($end_time)->toDateString()];
        if($type != null){
            $arr[] = ['d.id',$type];
        }
        (new Trail())->setTable("t")->from("trails as t")
            ->leftJoin('industries as i',"i.id",'=','t.industry_id')
            ->where($arr)
            ->groupBy("t.industry_id")
            ->get([
                DB::raw('count(t.id) as total'),
                "i.name as industry_name"
            ]);
    }

    /*********************************************项目报表*****************************************************/
    public function projectReport($start_time,$end_time,$type,$department)
    {
        $arr[] = ['p.created_at','>',Carbon::parse($start_time)->toDateString()];
        $arr[]  =   ['p.created_at','<',Carbon::parse($end_time)->toDateString()];
        if($department != null){
            $arr[] = ['du.department_id',$department];
        }
        if($type != null){
            $arr[]  = ['p.type',$type];
        }
        $peroject_list = (new Project())->setTable("p")->from("projects as p")
            ->leftJoin('users as u','u.id','=','p.principal_id')
            ->leftJoin('trail_star as ts','ts.trail_id','=','p.trail_id')
            ->where('ts.starable_type',ModuleableType::STAR)//艺人
            ->where('ts.type',TrailStar::EXPECTATION)//目标
            ->leftJoin('module_users as mu','mu.moduleable_id','=','ts.starable_id')
            ->where('mu.moduleable_type',ModuleableType::STAR)//艺人
            ->where('mu.type',ModuleUserType::BROKER)//经纪人
            ->leftjoin('department_user as du','du.user_id','=','mu.user_id')
            ->where($arr)
            ->get([
                DB::raw('distinct p.id'),
                'p.status','p.type','p.title',
                DB::raw('u.name as principal_name'),
                'p.trail_id'
            ]);
        foreach ($peroject_list as &$project){
            //查找部门
            $department_list = (new TrailStar())->setTable("ts")->from("trail_star as ts")
                ->where('ts.starable_type',ModuleableType::STAR)//艺人
                ->where('ts.type',TrailStar::EXPECTATION)//目标
                ->leftJoin('module_users as mu','mu.moduleable_id','=','ts.starable_id')
                ->where('mu.moduleable_type',ModuleableType::STAR)//艺人
                ->where('mu.type',ModuleUserType::BROKER)//经纪人
                ->leftjoin('department_user as du','du.user_id','=','mu.user_id')
                ->leftJoin('departments as d','d.id','=','du.department_id')
                ->where('ts.trail_id',$project->trail_id)
                ->get(['d.name']);
            foreach ($department_list->toArray() as $deparment){
                if(isset($deparment['name']) && $deparment['name'] != null)
                    $project->deparment_name .= ",".$deparment['name'];
            }
            //查找艺人
            $star_list = (new TrailStar())->setTable("ts")->from("trail_star as ts")
                ->where('ts.starable_type',ModuleableType::STAR)//艺人
                ->where('ts.type',TrailStar::EXPECTATION)//目标
                ->leftJoin('stars as s','s.id','=','ts.starable_id')
                ->where('ts.trail_id',$project->trail_id)
                ->get(['s.id','s.name']);
            foreach ($star_list->toArray() as $star){
                if(isset($star['name']) && $star['name'] != null)
                    $project->star_name .= ",".$star['name'];
            }
            $project->deparment_name = trim($project->deparment_name,",");
            $project->star_name = trim($project->star_name,",");
        }
        return $peroject_list;
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
        $arr[] = ['p.created_at','>',Carbon::parse($start_time)->toDateString()];
        $arr[]  =   ['p.created_at','<',Carbon::parse($end_time)->toDateString()];
        if($department != null){
            $arr[] = ['du.department_id',$department];
        }
        if($target_star != null){
            $arr[] = ['ts.starable_id',$target_star];
        }
        $peroject_list = (new Project())->setTable("p")->from("projects as p")
            ->leftJoin('users as u','u.id','=','p.principal_id')
            ->leftJoin('trail_star as ts','ts.trail_id','=','p.trail_id')
            ->where('ts.starable_type',ModuleableType::STAR)//艺人
            ->where('ts.type',TrailStar::EXPECTATION)//目标
            ->leftJoin('module_users as mu','mu.moduleable_id','=','ts.starable_id')
            ->where('mu.moduleable_type',ModuleableType::STAR)//艺人
            ->where('mu.type',ModuleUserType::BROKER)//经纪人
            ->leftjoin('department_user as du','du.user_id','=','mu.user_id')
            ->where($arr)
            ->groupBy(DB::raw("p.type,DATE_FORMAT(p.created_at,'%Y-%m')"))
            ->get([
                DB::raw('distinct p.id'),
                DB::raw('count(p.id)'),
                DB::raw("DATE_FORMAT(p.created_at,'%Y-%m')"),
                'p.type'
            ]);
        return $peroject_list;
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
        $arr[] = ['p.created_at','>',Carbon::parse($start_time)->toDateString()];
        $arr[]  =   ['p.created_at','<',Carbon::parse($end_time)->toDateString()];
        if($department != null){
            $arr[] = ['du.department_id',$department];
        }
        if($target_star != null){
            $arr[] = ['ts.starable_id',$target_star];
        }
        $result = (new Project())->setTable("p")->from("projects as p")
            ->leftJoin('trails as t','t.id','=','p.trail_id')
            ->leftJoin('industries as i','i.id','=','t.industry_id')
            ->groupBy(DB::raw('p.type,i.id'))
            ->get(
                [
                    DB::raw('count(p.id) as total'),
                    'i.name',
                    't.industry_id',
                    'p.type'
                ]
            );
        $um = array_sum(array_column($result->toArray(),'total'));
        $list = [];
        foreach ($result->toArray() as $value){
            $value['per'] = $um == 0 ? 0 : $value['total']/$um;
            $list[$value['type']] = $value;
        }
        return $list;

    }
    //客户报表
    public function clientReport($start_time,$end_time,$type=null)
    {
        $arr[] = ['c.created_at','>',Carbon::parse($start_time)->toDateString()];
        $arr[]  =   ['c.created_at','<',Carbon::parse($end_time)->toDateString()];
        if($type != null){
            $arr[] = ['c.type',$type];
        }
        $clients = (new Client())->setTable('c')->from('clients as c')
            ->leftJoin('users as u','u.id','=','c.principal_id')
            ->where($arr)
            ->get(['type','company','grade','keyman','u.name as principal_name']);
        foreach ($clients as $client){
            //获取联系人
            $contacts = $client->contacts()->get(['name','phone']);
            $client->contacts = $contacts;
        }
    }
    //客户分析
    public function clientAnalysis($start_time,$end_time)
    {
        $arr[] = ['created_at','>',Carbon::parse($start_time)->toDateString()];
        $arr[]  =   ['created_at','<',Carbon::parse($end_time)->toDateString()];
        Client::where($arr)
            ->groupBy(DB::raw("type,DATE_FORMAT(created_at,'%Y-%m')"))
            ->get([
                DB::raw("count(id) as total"),
                'type',
                DB::raw("DATE_FORMAT(created_at,'%Y-%m')")
            ]);
    }
    //签约中艺人报表Contract signing

    /**
     * 艺人报表
     * @param $start_time开始时间
     * @param $end_time结束时间
     * @param $sign_contract_status签约状态
     */
    public function starContractSigningReport($start_time,$end_time,$sign_contract_status)
    {
        $arr[] = ['created_at','>',Carbon::parse($start_time)->toDateString()];
        $arr[]  =   ['created_at','<',Carbon::parse($end_time)->toDateString()];
        $arr[] = ['sign_contract_status',$sign_contract_status];
        //签约中
        if($sign_contract_status == SignContractStatus::SIGN_CONTRACTING){
            $stars = Star::where($arr)
                ->select('sign_contract_status','name','birthday','source','communication_status','created_at','')
                ->get();
        }else{//已签约/解约
            //合同，预计订单收入，花费金额都没查呢
            $stars = (new Star())->setTable("s")->from("trails s")
                ->leftJoin("module_users as mu",function ($join){
                    $join->on('mu.moduleable_id','=','s.id')
                        ->on('mu.moduleable_type','=',ModuleableType::STAR)//艺人
                        ->on('mu.type','=',ModuleUserType::BROKER);//经纪人
                })->leftJoin("department_user as du",'du.user_id','=','mu.user_id')
                ->leftJoin("trails_star as ts",function ($join){
                    $join->on('ts.starable_id','=','s.id')
                        ->on('ts.starable_type','=',ModuleableType::STAR)//艺人
                        ->on('ts.type',TrailStar::EXPECTATION);//目标
                })->leftJoin('projects as p','p.trail_id','on','ts.trail_id')
                ->groupBy('s.id')
                ->get([
                    's.id','name','sign_contract_status',
                    DB::raw("count(ts.id) as trail_total"),
                    DB::raw("count(p.id) as project_total"),
                    DB::raw("GROUP_CONCAT(DISTINCT d.name) as department_name")
                ]);

        }
        return $stars;

    }

    /**
     * 艺人分析
     * @param $start_time
     * @param $end_time
     * @param $deparment
     * @param $target_star
     */
    public function starAnalysis($start_time,$end_time,$deparment,$target_star)
    {

    }


}
