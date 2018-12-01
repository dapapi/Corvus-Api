<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportForm\CommercialFunnelRequest;
use App\Repositories\TrailRepository;
use App\Repositories\TrailRepository2;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportFormController extends Controller
{
    //商业漏斗分析报表
    public function CommercialFunnelReportFrom(CommercialFunnelRequest $request)
    {
        $start_time = $request->get('start_time',Carbon::now()->addDay(-7)->toDateTimeString());
        $end_time = $request->get("end_time",Carbon::now()->toDateTimeString());
        return (new TrailRepository2())->CommercialFunnelReportFrom($start_time,$end_time);
    }
}
