<?php

namespace App\Repositories;

use App\Models\Project;
use App\Models\Star;
use App\Models\StarReport;
use App\Models\Task;
use App\TaskStatus;
use Carbon\Carbon;
use function foo\func;
use Illuminate\Support\Facades\DB;

class StarReportRepository
{
    /**
     * 获取明星粉丝数据
     * @param $star_id
     * @param $star_time
     * @param $end_time
     */
    public static function getFensiByStarId($star_id,$starable_type,$start_time,$end_time){
        $result = StarReport::where('created_at','>=',"$start_time")
            ->where('created_at','<=',"$end_time")
            ->where('starable_id',$star_id)
            ->where('starable_type',$starable_type)
            ->groupBy(DB::raw(
                "date_format(created_at,'%Y-%m-%d'),platform_id"
            ))
            ->get([
                DB::raw("avg(count)"),
                'platform_id',
                DB::raw("date_format(created_at,'%Y-%m-%d') as date")
            ]);
        return $result;
    }

    public static function getFiveProjectAndTask($star_id)
    {
        $projects = (new Star())->setTable('s')->from('stars as s')
            ->leftJoin('project_resources as pr','pr.resourceable_id','=','s.id')
            ->leftJoin('projects as p','p.id','=','pr.project_id')
            ->leftJoin('users as u','u.id','=','p.principal_id')
            ->where('pr.resourceable_type','star')
            ->where('s.id',$star_id)
            ->where('end_at','<=',Carbon::now()->toDateTimeString())
            ->where('p.status',Project::STATUS_NORMAL)//进行中
            ->limit(5)
            ->orderBy('p.created_at','desc')
            ->get(
                [
                    DB::raw('s.name as star_name'),
                    DB::raw('u.name as principal_name'),
                    'p.end_at',
                    'p.status',
                ]
            );
        $tasks = (new Star())->setTable('s')->from('stars as s')
            ->leftJoin('task_resources as tr','tr.resourceable_id','=','s.id')
            ->leftJoin('tasks as t','t.id','=','tr.task_id')
            ->leftJoin('users as u','u.id','=','t.principal_id')
            ->where('tr.resourceable_type','star')
            ->where('s.id',$star_id)
            ->where('t.end_at','<=',Carbon::now()->toDateTimeString())
            ->where('t.status',TaskStatus::NORMAL)
            ->orderBy('t.created_at','desc')
            ->limit(5)
            ->get(
                [
                    DB::raw('s.name as star_name'),
                    DB::raw('u.name as principal_name'),
                    't.end_at',
                    't.status',
                ]
            );
        return [
          'project' =>  $projects,
          'task'    =>  $tasks,
        ];
    }
}
