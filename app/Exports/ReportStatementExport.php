<?php

namespace App\Exports;
use App\Models\Report;
use App\Repositories\ReportFormRepository;
use Illuminate\Support\Facades\Auth;
use App\Models\Trail;
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

class ReportStatementExport implements FromQuery, WithMapping, WithHeadings
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


     $report =  Report::query();
     return $report;



    }
    /**
     * @param Blogger $blogger
     * @return array
     */
    public function map($report): array
    {
        $request = $this->request;
        $plo = $request->all();

        $start_time = $plo['start_time'];
        $end_time = $plo['end_time'];
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

//        $industry_data = $this->computeDate(
//            $current_industry_trail_number,
//            $prev_industry_trail_number,
//            $prev_year_industry_trail_number,
//            $current_industry_trail_confirm_number,
//            $prev_industry_trail_confirm_number,
//            $prev_year_industry_trail_confirm_number
//        );
        //计算接触数量总数
        $sum = array_sum(array_column($current_industry_trail_number->toArray(),'number'));
        $confirm_sum = array_sum(array_column($current_industry_trail_confirm_number->toArray(),'number'));
//        dd($current_industry_trail_number);
        //计算数量占比,计算同比
        array_map(function ($v) use ($sum,$current_industry_trail_number,$prev_industry_trail_number){
            $v->ratio = $sum == 0 ? 0 : $v->number/$sum; //数量占比

            //获取行业上一周期对应的接触数量
            $prev_arr = $current_industry_trail_number->toArray();
//            dd($prev_arr);
            $ring_key = array_search(intval($v->id),array_column($prev_arr,'id'));
            $v->ring_ratio_increment = $v->number-$prev_arr[$ring_key]->number;//接触环比增量

            //获取同比增量
            $prev_year_arr = $prev_industry_trail_number->toArray();
            $annual_key = array_search(intval($v->id),array_column($prev_year_arr,'id'));
            $v->annual_increment = $v->number-$prev_year_arr[$annual_key]->number;//接触同比增量
            return $v;
        },$current_industry_trail_number->toArray());
        //计算环比
        array_map(function ($v) use ($prev_industry_trail_confirm_number,$prev_year_industry_trail_confirm_number){
            $prev_confirm_arr = $prev_year_industry_trail_confirm_number->toArray();
            $prev_year_confirm_arr = $prev_year_industry_trail_confirm_number->toArray();

            //环比增量
            $prev_confirm_key = array_search($v->id,array_column($prev_confirm_arr,"id"));
            $v->confirm_ratio_increment = $v->number-$prev_confirm_arr[$prev_confirm_key]->number;

            //同比增量
            $prev_year_confirm_key = array_search($v->id,array_column($prev_year_confirm_arr,"id"));
            $v->confirm_annual_increment = $v->number - $prev_year_confirm_arr[$prev_year_confirm_key]->number;
            return $v;
        },$current_industry_trail_confirm_number->toArray());
        //合并两个数组 //将接触环比增量，接触同比增量，达成环比增量，达成同比增量算出总和
        $ring_ratio_increment_sum = 0;//接触环比总和
        $annual_ratio_increment_sum = 0;//接触同比总和
        $confirm_ratio_increment_sum = 0;//达成环比
        $confirm_annual_increment_sum = 0;//达成同比
        array_map(function ($v) use ($current_industry_trail_confirm_number,
            //  function ($v) use ($current_industry_trail_confirm_number,
            &$ring_ratio_increment_sum,&$annual_ratio_increment_sum,
            &$confirm_ratio_increment_sum,&$confirm_annual_increment_sum)
        {
            $current_confirm_Arr = $current_industry_trail_confirm_number->toArray();
            $ring_ratio_increment_sum += $v->ring_ratio_increment;
            $annual_ratio_increment_sum += $v->annual_increment;
            $v->ratio = floor($v->ratio * 10000) / 10000;
            $key = array_search($v->id, array_column($current_confirm_Arr, 'id'));
            $v->confirm_ratio_increment = $current_confirm_Arr[$key]->confirm_ratio_increment; //达成环比增量
            $v->confirm_annual_increment = $current_confirm_Arr[$key]->confirm_annual_increment; //达成同比增量
            $v->customer_conversion_rate = $v->number == 0 ? 0 : $current_confirm_Arr[$key]->number / $v->number;//客户转化率
            $v->customer_conversion_rate = floor($v->customer_conversion_rate * 10000) / 10000;
            $v->confirm_number = $current_confirm_Arr[$key]->number;//达成数量
            $confirm_ratio_increment_sum += $v->confirm_ratio_increment;
            $confirm_annual_increment_sum += $v->confirm_annual_increment;
            return $v;
            //  }
        }, $current_industry_trail_number->toArray());
//dd($current_industry_trail_number);

            return [
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                ''
            ];



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
    public function headings(): array
    {


                return [
                    '',
                    '',
                    '接触数量',
                    '数量占比',
                    '接触同比增量',
                    '达成数量',
                    '达成环比增量',
                    '达成同比增量',
                    '客户转化率'


                ];
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
