<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportForm\CommercialFunnelRequest;
use App\Repositories\TrailRepository;
use App\Repositories\TrailRepository2;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportFormController extends Controller
{
    //商业漏斗分析报表---商务报表
    public function CommercialFunnelReportFrom(CommercialFunnelRequest $request)
    {
        //默认分析7天
        $start_time = $request->get('start_time',Carbon::now()->addDay(-7)->toDateTimeString());
        $end_time = $request->get("end_time",Carbon::now()->toDateTimeString());
        return (new TrailRepository2())->CommercialFunnelReportFrom($start_time,$end_time);
    }
    //商业漏斗分析报表---销售漏斗
    public function salesFunnel(CommercialFunnelRequest $request){
        //默认分析7天
        $start_time = $request->get('start_time',Carbon::now()->addDay(-7)->toDateTimeString());
        $end_time = $request->get("end_time",Carbon::now()->toDateTimeString());
        return (new TrailRepository2())->salesFunnel($start_time,$end_time);
    }
    //销售线索报表
    public function trailReportFrom(Request $request){
        //默认分析7天
        $start_time = $request->get('start_time',Carbon::now()->addDay(-7)->toDateTimeString());
        $end_time = $request->get("end_time",Carbon::now()->toDateTimeString());
        $type = $request->get('type',null);
        $resource_tye = $request->get('resource_type',null);
        return (new TrailRepository2())->trailReportFrom($start_time,$end_time,$type,$resource_tye);
    }
    //线索新曾
    public function newTrail(Request $request)
    {
        //默认分析7天
        $start_time = $request->get('start_time',Carbon::now()->addDay(-7)->toDateTimeString());
        $end_time = $request->get("end_time",Carbon::now()->toDateTimeString());
        $type = $request->get('type',null);
        $resource_tye = $request->get('resource_type',null);
        $target_star = $request->get('target_star',null);
        $target_star = $target_star == null ? null :hashid_decode($target_star);
        return (new TrailRepository2())->newTrail($start_time,$end_time,$type,$resource_tye,$target_star);
    }
    //销售线索占比
    public function perTrail(Request $request)
    {
        //默认分析7天
        $start_time = $request->get('start_time',Carbon::now()->addDay(-7)->toDateTimeString());
        $end_time = $request->get("end_time",Carbon::now()->toDateTimeString());
        $type = $request->get('type',null);
        $resource_tye = $request->get('resource_type',null);
        $target_star = $request->get('target_star',null);
        $target_star = $target_star == null ? null :hashid_decode($target_star);
        return (new TrailRepository2())->percentageOfSalesLeads($start_time,$end_time,$type,$resource_tye,$target_star);
    }
}
