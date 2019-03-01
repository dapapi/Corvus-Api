<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportForm\CommercialFunnelRequest;
use App\Repositories\ReportFormRepository;
use Carbon\Carbon;
use App\Exports\BloggersStatementExport;
use App\Exports\TrailsStatementExport;
use App\Exports\ReportStatementExport;
use App\Exports\ClientsStatementExport;
use App\Exports\ProjectsStatementExport;
use App\Exports\StarsStatementExport;
use Illuminate\Http\Request;

class ReportFormController extends Controller
{
    //商业漏斗分析报表---商务报表
    public function CommercialFunnelReportFrom(CommercialFunnelRequest $request)
    {
        //默认分析7天
        $start_time = $request->get('start_time',Carbon::now()->addDay(-7)->toDateTimeString());
        $end_time = $request->get("end_time",Carbon::now()->toDateTimeString());
        return (new ReportFormRepository())->CommercialFunnelReportFrom($start_time,$end_time);
    }
    //商务报表报表导出
    public function reportExport(Request $request)
    {
        $file = '当前商务报表报表导出' . date('YmdHis', time()) . '.xlsx';
        return (new ReportStatementExport($request))->download($file);
    }
    //商业漏斗分析报表---销售漏斗
    public function salesFunnel(CommercialFunnelRequest $request){
        //默认分析7天
        $start_time = $request->get('start_time',Carbon::now()->addDay(-7)->toDateTimeString());
        $end_time = $request->get("end_time",Carbon::now()->toDateTimeString());
        return (new ReportFormRepository())->salesFunnel($start_time,$end_time);
    }
    //销售线索报表
    public function trailReportFrom(Request $request){
        //默认分析7天
        $start_time = $request->get('start_time',Carbon::now()->addDay(-7)->toDateTimeString());
        $end_time = $request->get("end_time",Carbon::now()->toDateTimeString());
        $type = $request->get('type',null);
        $department = $request->get('department',null);
        $department = $department == null ? null : hashid_decode($department);
        return (new ReportFormRepository())->trailReportFrom($start_time,$end_time,$type,$department);
    }
    //销售线索报表导出
    public function trailExport(Request $request)
    {
        $file = '当前销售线索报表导出' . date('YmdHis', time()) . '.xlsx';
        return (new TrailsStatementExport($request))->download($file);
    }
    //线索新曾
    public function newTrail(Request $request)
    {
        //默认分析7天
        $start_time = $request->get('start_time',Carbon::now()->addDay(-7)->toDateTimeString());
        $end_time = $request->get("end_time",Carbon::now()->toDateTimeString());
        $department = $request->get('department',null);
        $target_star = $request->get('target_star',null);
        $department = $department == null ? null : hashid_decode($department);
        $target_star = $target_star == null ? null :hashid_decode($target_star);
        return (new ReportFormRepository())->newTrail($start_time,$end_time,$department,$target_star);
    }
    //销售线索占比
    public function perTrail(Request $request)
    {
        //默认分析7天
        $start_time = $request->get('start_time',Carbon::now()->addDay(-7)->toDateTimeString());
        $end_time = $request->get("end_time",Carbon::now()->toDateTimeString());
        $department = $request->get('department',null);
        $target_star = $request->get('target_star',null);
        $department = $department == null ? null : hashid_decode($department);
        $target_star = $target_star == null ? null :hashid_decode($target_star);
        return (new ReportFormRepository())->percentageOfSalesLeads($start_time,$end_time,$department,$target_star);
    }
    //销售线索--行业分析
    public function industryAnalysis(Request $request)
    {
        //默认分析7天
        $start_time = $request->get('start_time',Carbon::now()->addDay(-7)->toDateTimeString());
        $end_time = $request->get("end_time",Carbon::now()->toDateTimeString());
        $type = $request->get('type',null);
        return (new ReportFormRepository())->industryAnalysis($start_time,$end_time,$type);
    }

    /**
     * 项目报表
     * @param Request $request
     * @return mixed
     */
    public function projectReport(Request $request)
    {
        $start_time = $request->get('start_time',Carbon::now()->addDay(-7)->toDateTimeString());
        $end_time = $request->get("end_time",Carbon::now()->toDateTimeString());
        $type = $request->get('type',null);
        $department = $request->get('department',null);
        $department = $department == null ? null : hashid_decode($department);
        return (new ReportFormRepository())->projectReport($start_time,$end_time,$type,$department);
    }
    //项目报表导出
    public function projectExport(Request $request)
    {
        $file = '当前项目报表导出' . date('YmdHis', time()) . '.xlsx';
        return (new ProjectsStatementExport($request))->download($file);
    }
    //项目新增
    public function newProject(Request $request)
    {
//        $start_time,$end_time,$department=null,$target_star=null
        $start_time = $request->get('start_time',Carbon::now()->addDay(-7)->toDateTimeString());
        $end_time = $request->get("end_time",Carbon::now()->toDateTimeString());
        $department = $request->get('department',null);
        $target_star = $request->get('target_star',null);
        $target_star = $target_star == null ? null :hashid_decode($target_star);
        $department = $department == null ? null : hashid_decode($department);
        return (new ReportFormRepository())->newProject($start_time,$end_time,$department,$target_star);
    }
    //项目占比
    public function percentageOfProject(Request $request)
    {
        $start_time = $request->get('start_time',Carbon::now()->addDay(-7)->toDateTimeString());
        $end_time = $request->get("end_time",Carbon::now()->toDateTimeString());
        $department = $request->get('department',null);
        $target_star = $request->get('target_star',null);
        $target_star = $target_star == null ? null :hashid_decode($target_star);
        $department = $department == null ? null : hashid_decode($department);
        return (new ReportFormRepository())->percentageOfProject($start_time,$end_time,$department,$target_star);
    }
    //客户报表
    public function clientReport(Request $request)
    {
        $start_time = $request->get('start_time',Carbon::now()->addDay(-7)->toDateTimeString());
        $end_time = $request->get("end_time",Carbon::now()->toDateTimeString());
        $type = $request->get('type',null);
        return (new ReportFormRepository())->clientReport($start_time,$end_time,$type);

    }
    //客户报表导出
    public function clientExport(Request $request)
    {
        $file = '当前客户报表导出' . date('YmdHis', time()) . '.xlsx';
        return (new ClientsStatementExport($request))->download($file);
    }
    //客户分析
    public function clientAnalysis(Request $request)
    {
        $start_time = $request->get('start_time',Carbon::now()->addDay(-7)->toDateTimeString());
        $end_time = $request->get("end_time",Carbon::now()->toDateTimeString());
        return (new ReportFormRepository())->clientAnalysis($start_time,$end_time);
    }
    public function starReport(Request $request)
    {
//        $start_time,$end_time,$sign_contract_status,$department=null,$p_type=null,$t_type=null
        $start_time = $request->get('start_time',Carbon::now()->addDay(-7)->toDateTimeString());
        $end_time = $request->get("end_time",Carbon::now()->toDateTimeString());
        $sign_contract_status = $request->get('sign_contract_status',null);
        $department = $request->get("departmnet",null);
        $department = $department == null ? null : hashid_decode($department);
        $target_star = $request->get('target_star',null);
        $target_star = $target_star == null ? null :hashid_decode($target_star);
        $type = $request->get('type',null);

        return (new ReportFormRepository())->starReport($start_time,$end_time,$sign_contract_status,$department,$target_star,$type);
    }
    //艺人导出
    public function starExport(Request $request)
    {
        $file = '当前艺人报表导出' . date('YmdHis', time()) . '.xlsx';
        return (new StarsStatementExport($request))->download($file);
    }
    //艺人线索分析
    public function starTrailAnalysis(Request $request)
    {
        $start_time = $request->get('start_time',Carbon::now()->addDay(-7)->toDateTimeString());
        $end_time = $request->get("end_time",Carbon::now()->toDateTimeString());
        $department = $request->get('department',null);
        $target_star = $request->get('target_star',null);
        $target_star = $target_star == null ? null :hashid_decode($target_star);
        $department = $department == null ? null : hashid_decode($department);
        return (new ReportFormRepository())->starTrailAnalysis($start_time,$end_time,$department,$target_star);
    }
    //艺人项目
    public function starProjectAnalysis(Request $request)
    {
        $start_time = $request->get('start_time',Carbon::now()->addDay(-7)->toDateTimeString());
        $end_time = $request->get("end_time",Carbon::now()->toDateTimeString());
        $department = $request->get('department',null);
        $target_star = $request->get('target_star',null);
        $target_star = $target_star == null ? null :hashid_decode($target_star);
        $department = $department == null ? null : hashid_decode($department);
        return (new ReportFormRepository())->starProjectAnalysis($start_time,$end_time,$department,$target_star);
    }

    //博主报表
    public function bloggerReport(Request $request)
    {
        $start_time = $request->get('start_time',Carbon::now()->addDay(-7)->toDateTimeString());//开始时间
        $end_time = $request->get("end_time",Carbon::now()->toDateTimeString());//结束时间
        $sign_contract_status = $request->get('sign_contract_status',null);//签约状态
        $department = $request->get('department',null);//组别
//        $target_star = $request->get('target_star',null);//目标艺人
//        $target_star = $target_star == null ? null : hashid_decode($target_star);
        $trail_type = $request->get("trail_type",null);//线索类型
        $project_type = $request->get("project_type",null);//项目类型
        $department = $department == null ? null : hashid_decode($department);
        return (new ReportFormRepository())->bloggerReport($start_time,$end_time,$sign_contract_status,$department,$trail_type,$project_type);
    }
    public function bloggerExport(Request $request)
    {
        $file = '当前博主报表导出' . date('YmdHis', time()) . '.xlsx';
        return (new BloggersStatementExport($request))->download($file);
    }
    //博主线索分析
    public function bloggerTrailAnalysis(Request $request)
    {
        $start_time = $request->get('start_time',Carbon::now()->addDay(-7)->toDateTimeString());
        $end_time = $request->get("end_time",Carbon::now()->toDateTimeString());
        $department = $request->get('department',null);
        $target_star = $request->get('target_star',null);
        $target_star = $target_star == null ? null :hashid_decode($target_star);
        $department = $department == null ? null : hashid_decode($department);
        return (new ReportFormRepository())->bloggerTrailAnalysis($start_time,$end_time,$department,$target_star);
    }
    //博主项目分析
    public function bloggerProjectAnalysis(Request $request)
    {
        $start_time = $request->get('start_time',Carbon::now()->addDay(-7)->toDateTimeString());
        $end_time = $request->get("end_time",Carbon::now()->toDateTimeString());
        $department = $request->get('department',null);
        $target_star = $request->get('target_star',null);
        $target_star = $target_star == null ? null :hashid_decode($target_star);
        $department = $department == null ? null : hashid_decode($department);
        return (new ReportFormRepository())->bloggerProjectAnalysis($start_time,$end_time,$department,$target_star);
    }

}
