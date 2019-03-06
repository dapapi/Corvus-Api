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
use Maatwebsite\Excel\Facades\Excel;

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
        //$file = '当前商务报表报表导出' . date('YmdHis', time()) . '.xlsx';
      //  return Excel::download(new ReportStatementExport($request), 'invoices.xlsx');
      //  return (new ReportStatementExport($request))->download($file);
        $start_time = $request->get('start_time',Carbon::now()->addDay(-7)->toDateTimeString());
        $end_time = $request->get("end_time",Carbon::now()->toDateTimeString());
        $dataArray = (new ReportFormRepository())->CommercialFunnelReportFrom($start_time,$end_time);
        $filename = '当前商务报表报表导出' . date('YmdHis', time()) ;
      //  $filename = iconv('UTF-8',"GB2312//IGNORE",$filename);
        $filename = 444;
        header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Content-type:application/vnd.ms-excel");
        header('Content-type: xls/csv');
        header("Content-Disposition:attachment;filename=aa.xls");
        header("Pragma: no-cache");
        header("Expires: 0");
        header("Access-Control-Allow-Origin:*");
//        header('Content-Type: application/octet-stream');
//        header("Content-type: application/vnd.ms-excel; charset=gbk");
        Header("Access-Control-Allow-Credentials:true");
//        header('Cache-Control: max-age=0');
//        header("Content-Disposition: attachment; filename=".$filename.".xls");


        $data = '';
        $data .= "<table border='1'>";
        $data .= "<tr><td colspan='1'>     </td><td colspan='1'>       </td><td colspan='1'>接触数量 </td><td colspan='1'>数量占比</td><td colspan='1'>接触环比增量</td><td colspan='1'>接触同比增量</td><td colspan='1'>达成数量</td>
         <td colspan='1'>达成环比增量 </td><td colspan='1'>达成同比增量 </td><td colspan='1'>客户转化率 </td></tr>";
        $data .= "<tr><td colspan='1'>     </td><td colspan='1'>       </td><td colspan='1'>".$dataArray['sum']."</td><td colspan='1'>".$dataArray['ratio_sum']*100..'%'."</td><td colspan='1'>".$dataArray['ring_ratio_increment_sum']."</td><td colspan='1'>".$dataArray['annual_ratio_increment_sum']."</td>
         <td colspan='1'>".$dataArray['confirm_annual_increment_sum']."</td><td colspan='1'>".$dataArray['confirm_ratio_increment_sum']." </td><td colspan='1'>".$dataArray['confirm_sum']." </td><td colspan='1'>".$dataArray['customer_conversion_rate_sum']*100..'%' ." </td></tr>";
        $data .= "</table>";
        $data .= "<br>";
        foreach($dataArray['data']['industry_data'] as $key => $val)
        {

            $key = $key==0?'品类':'';
            $data .= "<table border='1'>";
            $data .= "<tr><td colspan='1'>".$key."</td><td colspan='1'>$val->name</td><td colspan='1'>$val->number</td><td colspan='1'>".$val->ratio*100..'%'." </td><td colspan='1'>$val->ring_ratio_increment</td><td colspan='1'>$val->annual_increment</td><td colspan='1'>$val->confirm_number </td><td colspan='1'>$val->confirm_annual_increment</td>
         <td colspan='1'>$val->confirm_ratio_increment </td><td colspan='1'>".$val->customer_conversion_rate.'%'." </td></tr>";      $data .= "</table>";
            $data .= "<br>";
        }
        foreach($dataArray['data']['cooperation_data'] as $key => $val)
        {
            $key = $key==0?'合作':'';
            $data .= "<table border='1'>";
            $data .= "<tr><td colspan='1'>".$key."</td><td colspan='1'>$val->name</td><td colspan='1'>$val->number</td><td colspan='1'>".$val->ratio*100..'%'." </td><td colspan='1'>$val->ring_ratio_increment</td><td colspan='1'>$val->annual_increment</td><td colspan='1'>$val->confirm_number </td><td colspan='1'>$val->confirm_annual_increment</td>
         <td colspan='1'>$val->confirm_ratio_increment </td><td colspan='1'>".$val->customer_conversion_rate.'%'." </td></tr>";
            $data .= "</table>";
            $data .= "<br>";
        }
        foreach($dataArray['data']['resource_type_data'] as $key => $val)
        {
            $key = $key==0?'线索来源':'';
            $data .= "<table border='1'>";
            $data .= "<tr><td colspan='1'>".$key."</td><td colspan='1'>$val->name</td><td colspan='1'>$val->number</td><td colspan='1'>".$val->ratio*100..'%'." </td><td colspan='1'>$val->ring_ratio_increment</td><td colspan='1'>$val->annual_increment</td><td colspan='1'>$val->confirm_number </td><td colspan='1'>$val->confirm_annual_increment</td>
         <td colspan='1'>$val->confirm_ratio_increment </td><td colspan='1'>".$val->customer_conversion_rate.'%'." </td></tr>";     $data .= "</table>";
            $data .= "<br>";
        }
        foreach($dataArray['data']['priority_data'] as $key => $val)
        {
            $key = $key==0?'优先级':'';
            $data .= "<table border='1'>";
            $data .= "<tr><td colspan='1'>".$key."</td><td colspan='1'>$val->name</td><td colspan='1'>$val->number</td><td colspan='1'>".$val->ratio*100..'%'." </td><td colspan='1'>$val->ring_ratio_increment</td><td colspan='1'>$val->annual_increment</td><td colspan='1'>$val->confirm_number </td><td colspan='1'>$val->confirm_annual_increment</td>
         <td colspan='1'>$val->confirm_ratio_increment </td><td colspan='1'>".$val->customer_conversion_rate.'%'." </td></tr>";     $data .= "</table>";
            $data .= "<br>";
        }
        $data.='</table>';
    //    dd($data);
//        if (EC_CHARSET != 'gbk')
//        {
//            echo yzy_iconv(EC_CHARSET, 'gbk', $data) . "\t";
//        }
//        else {
        echo $data . "\t";
        //  }
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
        $client = $request->get('client','pc');//终端
        return (new ReportFormRepository())->newTrail($start_time,$end_time,$department,$target_star,$client);
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
        return (new ReportFormRepository())->industryAnalysis($start_time,$end_time,$type)->toArray();
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
        $client = $request->get("client",'pc');
        return (new ReportFormRepository())->newProject($start_time,$end_time,$department,$target_star,$client);
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
        $_client = $request->get('client','pc');
        return (new ReportFormRepository())->clientAnalysis($start_time,$end_time,$_client);
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
//        $target_star = $target_star == null ? null :hashid_decode($target_star);
        $trail_type = $request->get("trail_type",null);
        $project_type = $request->get("project_type",null);

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
