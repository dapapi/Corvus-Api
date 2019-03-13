<?php

namespace App\Repositories;

use App\Models\Project;
use App\Models\Star;
use App\Models\StarReport;
use App\Models\Task;
use App\ModuleUserType;
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
                    DB::raw(
                        "case p.status
                        when 1 then '进行中'
                        when 2 then '完成'
                        when 3 then '终止'
                        when 4 then '删除'
                        end status_name
                        "),
                    'p.status',
                    'p.title',
                    DB::raw(
                        "case p.type
                        when 1 then '影视项目'
                        when 2 then '综艺项目'
                        when 3 then '商务代言'
                        when 4 then 'papi项目'
                        when 5 then '基础项目'
                        end type_name"
                    ),
                    'p.type'
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
                    DB::raw(
                        "case t.status
                        when 1 then '正常'
                        when 2 then '完成'
                        when 3 then '终止'
                        end status_name"
                    ),
                    't.status',
                ]
            );
        return [
          'project' =>  $projects,
          'task'    =>  $tasks,
        ];
    }

}
