<?php

namespace App\Http\Controllers;

use App\Repositories\StarReportRepository;
use Illuminate\Http\Request;

class StarReportController extends Controller
{
    /**
     * 获取粉丝数据
     * @param Request $request
     */
    public function getStarFensi(Request $request)
    {
        $star_id = $request->get('star_id','null');
        $star_id = hashid_decode($star_id);
        $starable_type = $request->get('starable_type',null);
        $star_time = $request->get('start_time');
        $end_time = $request->get('end_time');
        $reports = StarReportRepository::getFensiByStarId($star_id,$starable_type,$star_time,$end_time);
        return $reports;
    }
}
