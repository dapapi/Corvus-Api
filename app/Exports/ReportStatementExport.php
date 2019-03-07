<?php

namespace App\Exports;
use App\Models\Report;
//use App\Models\Trail;
use App\Repositories\ReportFormRepository;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
//use Illuminate\Support\Facades\DB;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;


class ReportStatementExport implements  FromView
{

    use Exportable;
    public function __construct($request)
    {
        $this->request = $request;
    }

    public function view(): View
    {
        $request = $this->request;
        //默认分析7天
        $start_time = $request->get('start_time',Carbon::now()->addDay(-7)->toDateTimeString());
        $end_time = $request->get("end_time",Carbon::now()->toDateTimeString());
        $invoices = (new ReportFormRepository())->CommercialFunnelReportFrom($start_time,$end_time);
        return view('reportform.index', compact('invoices')

        );
    }

//    /**
//     * @return \Illuminate\Support\Collection
//     */
//    public function query()
//    {
//
//
//     $report =  Report::query();
//
//     return $report;
//
//
//
//    }
//    /**
//     * @param Blogger $blogger
//     * @return array
//     */
//    public function map($report): array
//    {
//        $request = $this->request;
//        //默认分析7天
//        $start_time = $request->get('start_time',Carbon::now()->addDay(-7)->toDateTimeString());
//        $end_time = $request->get("end_time",Carbon::now()->toDateTimeString());
//        $data =  (new ReportFormRepository())->CommercialFunnelReportFrom($start_time,$end_time);
//
//
//            return [
//                '',
//                '',
//                '',
//                '',
//                '',
//                '',
//                '',
//                ''
//            ];
//
//
//
//    }
//
//
//    public function headings(): array
//    {
//
//
//                return [
//                    '',
//                    '',
//                    '接触数量',
//                    '数量占比',
//                    '接触同比增量',
//                    '达成数量',
//                    '达成环比增量',
//                    '达成同比增量',
//                    '客户转化率'
//
//
//                ];
//        }

}
