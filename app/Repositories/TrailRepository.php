<?php

namespace App\Repositories;

use App\Models\Industry;
use App\Models\Trail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TrailRepository
{

    public function CommercialFunnelReportFrom($start_time,$end_time)
    {
//        $curr_trails_confirm_number = $this->getEveryIndustryTrailConfrimNumber('ss',$start_time,$end_time);
//        $curr_trails_number= $this->getEveryIndustryTrailNumber("ss",$start_time,$end_time);
        //获取查询周期
        $rang = Carbon::parse($start_time)->diffInDays($end_time);
        //上一周期开始时间和结束时间
        $prev_range_start = $start_time;
        $prev_range_end = Carbon::parse($start_time)->addDay(-$rang)->toDateTimeString();
        $prev_trails_confirm_number = $this->getEveryIndustryTrailNumber($start_time,$end_time,"ss");
//        $prev_trails_number = $this->getEveryIndustryTrailNumber("ss",$prev_range_start,$prev_range_end);

        $prev_year_start = Carbon::parse($start_time)->addYear(-1)->toDateTimeString();
        $prev_year_end = Carbon::parse($end_time)->addYear(-1)->toDateTimeString();
//        $prev_year_confirm_number = $this->getEveryIndustryTrailConfrimNumber("ss",$prev_year_start,$prev_year_end);
//        $prev_year_number = $this->getEveryIndustryTrailNumber("ss",$prev_year_start,$prev_year_end);

        return $prev_trails_confirm_number;

        $current_range_query = $this->subQuery($start_time,$end_time);
        $prev_range_query = $this->subQuery($prev_range_start,$prev_range_end);
        $last_year_range_query = $this->subQuery($prev_year_start,$prev_year_end);

//        Illuminate\Database\Query\Builder
        DB::connection()->enableQueryLog();
        $result = DB::table(DB::raw("({$current_range_query->toSql()}) as current"))
            ->select(
                "current.i_id as current_i_id",
                DB::raw("case  when current.fenhe is null then 0 else current.fenhe end current_fenhe"),
                DB::raw("case  when current.fen_dacheng is null then 0 else current.fenhe end current_fen_dacheng"),
                DB::raw("case  when current.he is null then 0 else current.he end current_he"),
                DB::raw("case  when current.dacheng is null then 0 else current.dacheng end current_dacheng"),
                "current.name as current_name",
                "prev.i_id as prev_i_id",
                DB::raw("case  when prev.fenhe is null then 0 else prev.fenhe end  prev_fenhe"),
                DB::raw("case  when prev.fen_dacheng is null then 0 else prev.fen_dacheng end prev_fen_dacehng"),
                DB::raw("case  when prev.he is null then 0 else prev.he end prev_he"),
                DB::raw("case  when prev.dacheng is null then 0 else prev.dacheng end prev_dacheng"),
                "prev.name as prev_name",
                "last.i_id as last_i_id",
                DB::raw("case  when last.fenhe is null then 0 else last.fenhe end last_fenhe"),
                DB::raw("case  when last.fen_dacheng is null then 0 else last.fen_dacheng end lase_fen_dacheng"),
                DB::raw("case  when last.he is null then 0 else last.he end last_he"),
                DB::raw("case  when last.dacheng is null then 0 else last.dacheng end last_dacheng"),
                "last.name as last_name",
                DB::raw("current.fenhe/current.he as zhanbi"),
                DB::raw("ifnull(current.fenhe,0)-ifnull(prev.fenhe,0) as huanbizengliang"),
                DB::raw("ifnull(current.fenhe,0)-ifnull(last.fenhe,0) as tongbizengliang"),
                DB::raw("ifnull(current.fen_dacheng,0)-ifnull(prev.fen_dacheng,0) as dachenghuanbi"),
                DB::raw("ifnull(current.fen_dacheng,0)-ifnull(last.fen_dacheng,0) as dachengtongbi"),
                DB::raw("ifnull(current.fen_dacheng,0)/ifnull(current.fenhe,0) as zhaunhualv")

                )
            ->leftJoin(DB::raw("({$prev_range_query->toSql()}) as prev"),"prev.i_id","=","current.i_id")
            ->leftJoin(DB::raw("({$last_year_range_query->toSql()}) as last"),"last.i_id","=","current.i_id")
            ->mergeBindings($current_range_query)
            ->mergeBindings($prev_range_query)
            ->mergeBindings($last_year_range_query)
            ->get();
        $sql = DB::getQueryLog();
//        dd($sql);
        return $result;
//        return $current_range_query->get();
    }

    private function subQuery($start_time,$end_time)
    {
        //销售线索总和
        $sum_trails_query = DB::table("trails")->select(DB::raw("count(id)"))
            ->where('created_at','>=',$start_time)
            ->where('created_at','<=',$end_time);
        //达成销售线索总和
        $sum_trails_confirm_query = DB::table("trails")
            ->where('status',Trail::STATUS_CONFIRMED)->select(DB::raw("count(id)"))
            ->where('created_at','>=',$start_time)
            ->where('created_at','<=',$end_time);
        //销售线索各行业达成情况
        $sum_industry_confirm_query = DB::table("trails")
            ->where('status',Trail::STATUS_CONFIRMED)
            ->where('created_at','>=',$start_time)
            ->where('created_at','<=',$end_time)
            ->groupBy("industry_id")
            ->select("industry_id",DB::raw("count(id) as fen_dacheng"));
        $sub_query = DB::table("trails as t")
            ->select(
                "t.id","i.id as i_id","tt.fen_dacheng","i.name",
                DB::raw("count(t.id) as fenhe"),
                DB::raw("({$sum_trails_query->toSql()}) as he"),
                DB::raw("({$sum_trails_confirm_query->toSql()}) as dacheng")
            )
            ->rightJoin("industries as i",'i.id','=','t.industry_id')
            ->leftJoin(DB::raw("({$sum_industry_confirm_query->toSql()}) as tt"),"tt.industry_id",'=','i.id')
            ->where('t.created_at','>=',$start_time)
            ->where('t.created_at','<=',$end_time)
            ->mergeBindings($sum_industry_confirm_query)
            ->mergeBindings($sum_trails_query)
            ->mergeBindings($sum_trails_confirm_query)
            ->groupBy("t.industry_id");
            //->get();
        return $sub_query;
    }
    //根据合作类型分类
    public function getDataByCooperationType($start_time,$end_time)
    {
        //销售线索总和
        $sum_trails_query = DB::table("trails")->select(DB::raw("count(id)"))
            ->where('created_at','>=',$start_time)
            ->where('created_at','<=',$end_time);
        //达成销售线索总和
        $sum_trails_confirm_query = DB::table("trails")
            ->where('status',Trail::STATUS_CONFIRMED)->select(DB::raw("count(id)"))
            ->where('created_at','>=',$start_time)
            ->where('created_at','<=',$end_time);
        //销售线索各行业达成情况
        $sum_industry_confirm_query = DB::table("trails")
            ->where('status',Trail::STATUS_CONFIRMED)
            ->where('created_at','>=',$start_time)
            ->where('created_at','<=',$end_time)
            ->groupBy("cooperation_type")
            ->select("industry_id",DB::raw("count(id) as fen_dacheng"));
        $sub_query = DB::table("trails as t")
            ->select(
                "t.id","i.id as i_id","tt.fen_dacheng","i.name",
                DB::raw("count(t.id) as fenhe"),
                DB::raw("({$sum_trails_query->toSql()}) as he"),
                DB::raw("({$sum_trails_confirm_query->toSql()}) as dacheng")
            )
            ->leftJoin(DB::raw("({$sum_industry_confirm_query->toSql()}) as tt"),"tt.industry_id",'=','i.id')
            ->where('t.created_at','>=',$start_time)
            ->where('t.created_at','<=',$end_time)
            ->mergeBindings($sum_industry_confirm_query)
            ->mergeBindings($sum_trails_query)
            ->mergeBindings($sum_trails_confirm_query)
            ->groupBy("t.industry_id");
        //->get();
        return $sub_query;
    }


    //获取每个行业的线索数量
    private function getEveryIndustryTrailNumber($start_time,$end_time,$where=null)
    {
        $arr = [];
        $arr[] = ['t.created_at','>=',$start_time];
        $arr[] = ['t.created_at','<=',$end_time];
        if($where != null){
            $arr[] = ['status',Trail::STATUS_CONFIRMED];
        }
        DB::connection()->enableQueryLog();
        (new Industry())->setTable('i')->from('industries as i')
            ->leftJoin("trails as t",'i.id','=','t.industry_id')
            ->where($arr)
            ->get(
                [
                    DB::raw("COUNT(t.id) as TrailNumber"),//销售线索数量
                    'i.name as industry_name',//行业名称
                    'i.id as industry_id',//行业ID
                ]
            );
        $sql = DB::getQueryLog();
        dd($sql);
    }
    private function getEveryIndustryTrailNumberByCooperationType($start_time,$end_time,$where=null)
    {

    }


    //获取每个行业的达成数量
    private function getEveryIndustryTrailConfrimNumber($group_colum,$start_time,$end_time)
    {
        return (new Trail())->setTable('t')->from('trails as t')
            ->rightJoin("industries as i",'i.id','=','t.industry_id')
            ->where('t.created_at','>=',$start_time)
            ->where('t.created_at','<=',$end_time)
            ->where('status',Trail::STATUS_CONFIRMED)
            ->groupBy("i.id")
            ->get(
                [
                    DB::raw("COUNT(t.id) as TrailNumber"),//销售线索数量
                    'i.name as industry_name',//行业名称
                    'i.id as industry_id',//行业ID
                ]
            );
    }

}
