<?php

namespace App\Repositories;


use App\Models\Attendance;
use App\Models\Users;
use App\Models\Department;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use phpDocumentor\Reflection\Types\Self_;

class AttendanceRepository
{
    /**
     * 考勤规则
     * 加班
     * 小于30分钟不算
     * 30分钟算半个小时
     * 小于45分钟算半个小时
     * 大于45分钟算1个小时
     *
     * 请假
     * 小于30分钟不算
     * 30分钟算半个小时
     * 小于45分钟算半个小时
     * 大于45分钟算一个小时
     *
     * 出差
     * 小于30分钟不算
     * 30分钟算半个小时
     * 小于45分钟算半个小时
     * 大于45分钟算一个小时
     *
     * 外勤
     * 小于30分钟不算
     * 30分钟算半个小时
     * 小于45分钟算半个小时
     * 大于45分钟算一个小时
     */
    /**
     * 计算 时间段内考勤天数
     * @param $minute  分钟数
     */
    private  static  function computeDay($minute){
        $hours = intval($minute/60);
        $mod_minute = $minute % 60;
        if($mod_minute < 30){//没有加班
            $hours += 0;
        }elseif($mod_minute >= 30 && $mod_minute < 45){
            $hours += 0.5;
        }elseif ($mod_minute >= 45 && $mod_minute <= 60){
            $hours += 1;
        }
        if($hours >8){//一天超过八小时算8小时
            $hours = 8;
        }
        return $hours/8;
    }
    public static function myselfStatistics(User $user,$year){

        //获取今年的开始时间和结束时间
        $start_year = Carbon::create($year,1,1,0,0,0);
        $end_year = Carbon::create($year,12,31,23,59,59);

        //获取用户今年的考勤
        $myselfAttendance = Attendance::whereRaw('start_at < ?',[$end_year])
        ->whereRaw('end_at > ?',[$start_year])
        ->where('creator_id',$user->id)
        ->get([

            'start_at',
            'end_at',
            'type',
            'number',
            DB::raw(//申请类型 1:请假 2:加班 3:出差 4:外勤
                "case type
                when 1 then '请假'
                when 2 then '加班'
                when 3 then '出差'
                when 4 then '外勤'
                else '数据错误'
                end type_name"
            )
        ]);
//        dd($myselfAttendance);
        $list = [];
        $daynumber = [];
        foreach ($myselfAttendance as $attendance){
            $start_time = Carbon::parse($attendance['start_at']);
            $end_time = Carbon::parse($attendance['end_at']);
            $type = $attendance['type'];
            $number = $attendance['number'];
            if($start_time->timestamp < $start_year->timestamp){
                $start_time = $start_year;
            }
            if($end_time->timestamp > $end_year->timestamp){
                $end_time = $end_year;
            }
            for($day = $start_time->dayOfYear;$day<=$end_time->dayOfYear;$day++){
                $month = $start_time->copy()->addDay($day-$start_time->dayOfYear)->month;
                $minute = 0;
                if($start_time->dayOfYear == $day){//第一天
                    $minute = Carbon::tomorrow()->diffInMinutes($start_time);//加班时长
                }elseif($end_time->dayOfYear == $day){//最后一天
                    $minute = $end_time->diffInMinutes(Carbon::create($end_time->year,$end_time->month,$end_time->day,0,0,0));
                }else{
                    $minute = 8*60;
                }

                $month_arr = array_column($daynumber,'month');
                $month_key = array_search($month,$month_arr);
                if($month_key>=0 && $month_key !== false){
                    $type_Arr = array_column($daynumber[$month_key]['daynumber'],'type');
                    $type_key = array_search($type,$type_Arr);
                    if($type_key >= 0 && $type_key !== false){

                        $daynumber[$month_key]['daynumber'][$type_key]['number'] += self::computeDay($minute);
                    }else{
                        $daynumber[$month_key]['daynumber'][] = [
                            'type'  =>  $type,
                            'number'    =>  self::computeDay($minute)
                        ];
                    }
                }else{
                    $daynumber[] = [
                        'month' =>  $month,
                        'daynumber' =>  [
                            [
                                'type'  =>  $type,
                                'number'    =>  self::computeDay($minute)
                            ]
                        ]
                    ];
                }

            }
        }
        //没有值得月份设置为空
        $month_arr2 = array_column($daynumber,'month');
        for($i = 1;$i<=12;$i++){
            $month_key2 = array_search($i,$month_arr2);
            if($month_key2 === false){
                $daynumber[] = [
                    'month' =>  $i,
                    'daynumber' =>  null,
                ];
            }
        }
       return $daynumber;
    }

    public static function myselfLeavelStatistics(User $user,$year)
    {
        //获取今年的开始时间和结束时间
        $start_year = Carbon::create($year,1,1,0,0,0);
        $end_year = Carbon::create($year,12,31,23,59,59);
        $myselfLeavelStatistics = $myselfAttendance = Attendance::whereRaw('start_at < ?',[$end_year])
            ->whereRaw('end_at > ?',[$start_year])
            ->where('creator_id',$user->id)
            ->where('type',Attendance::LEAVE)//请假
            ->get([
                'start_at',
                'end_at',
                'type',
                'number',
                'leave_type',
                'creator_id',
                DB::raw(//申请类型 1:请假 2:加班 3:出差 4:外勤
                    "case type
                when 1 then '请假'
                when 2 then '加班'
                when 3 then '出差'
                when 4 then '外勤'
                else '数据错误'
                end type_name"
                ),
                DB::raw(
                    "case leave_type
                when 1 then '事假'
                when 2 then '病假'
                when 3 then '调休假'
                when 4 then '年假'
                when 5 then '婚假'
                when 6 then '产假'
                when 7 then '陪产假'
                when 8 then '丧假'
                when 9 then '其他'
                else null end leave_type"
                )

            ]);
        $daynumber = [];
        foreach ($myselfLeavelStatistics as $leavelStatistic){
            $start_at = $leavelStatistic['start_at'];
            $end_at = $leavelStatistic['end_at'];
            $leavelStatistic['creator_id'] = hashid_encode($leavelStatistic['creator_id']);
            if($start_at < $start_year){
                $start_at = $start_year;
            }
            if($end_at > $end_year){
                $end_at = $end_year;
            }
            $start_time = Carbon::parse($start_at);
            $end_time = Carbon::parse($end_at);
            $leavel_type = $leavelStatistic['leave_type'];
            for($day = $start_time->dayOfYear;$day<$end_time->dayOfYear+1;$day++){
                $month = $start_time->copy()->addDay($day-$start_time->dayOfYear)->month;
                $minute = 0;
                if($start_time->dayOfYear == $day){//第一天
                    $minute = Carbon::tomorrow()->diffInMinutes($start_time);//加班时长
                }elseif($end_time->dayOfYear == $day){//最后一天
                    $minute = $end_time->diffInMinutes(Carbon::create($end_time->year,$end_time->month,$end_time->day,0,0,0));
                }else{
                        $minute = 8*60;
                }

                $month_arr = array_column($daynumber,'month');
                $month_key = array_search($month,$month_arr);
                if($month_key>=0 && $month_key !== false){
                    $leave_type_Arr = array_column($daynumber[$month_key]['daynumber'],'leave_type');
                    $type_key = array_search($leavel_type,$leave_type_Arr);
                    if($type_key >= 0 && $type_key !== false){

                        $daynumber[$month_key]['daynumber'][$type_key]['number'] += self::computeDay($minute);
                    }else{
                        $daynumber[$month_key]['daynumber'][] = [
                            'leave_type'  =>  $leavel_type,
                            'number'    =>  self::computeDay($minute)
                        ];
                    }
                }else{
                    $daynumber[] = [
                        'month' =>  $month,
                        'daynumber' =>  [
                            [
                                'leave_type'  =>  $leavel_type,
                                'number'    =>  self::computeDay($minute)
                            ]
                        ]
                    ];
                }
            }
        }
        //没有值得月份设置为空
        $month_arr2 = array_column($daynumber,'month');
        for($i = 1;$i<=12;$i++){
            $month_key2 = array_search($i,$month_arr2);
            if($month_key2 === false){
                $daynumber[] = [
                    'month' =>  $i,
                    'daynumber' =>  null,
                ];
            }
        }
        return ["data"  =>  $daynumber];
    }

    /**
     * 根据条件
     * @param $department 组织架构ID
     * @param $start_time 开始时间
     * @param $end_time   结束时间
     */
    public static function statistics($department,$start_time,$end_time)
    {
        $where = [];
        if(!is_null($start_time)){
            $where[] = ['end_at','>',$start_time];
        }
        if(!is_null($end_time)){
            $where[] = ['start_at','<',$end_time];
        }
//        DB::connection()->enableQueryLog();

        //获取要查询部门的子级部门
        $departments_list = (new Department())->getSubidByPid($department);
        $department_id_list = array_column($departments_list,'id');
        array_push($department_id_list,$department);

        $statistics = (new Attendance())
            ->setTable('a')
            ->from('attendances as a')
            ->leftJoin('department_user as d','a.creator_id','=','d.user_id')
            ->leftJoin('users as u','u.id','=','a.creator_id')
            ->where($where)
            ->whereIn('d.department_id',$department_id_list)
            ->get(
                [
                    'creator_id',
                    'u.icon_url',
                    'u.name',
                    'a.type',
                    'start_at',
                    'end_at',
                    DB::raw(//申请类型 1:请假 2:加班 3:出差 4:外勤
                        "case a.type
                when 1 then '请假'
                when 2 then '加班'
                when 3 then '出差'
                when 4 then '外勤'
                else '数据错误'
                end type_name"
                    ),
                ]
            );
        $daynumber = [];
        foreach ($statistics as $statistic){
            $start_at = Carbon::parse($statistic['start_at']);
            $end_at = Carbon::parse($statistic['end_at']);
            $start_time = Carbon::parse($start_time);
            $end_time = Carbon::parse($end_time);
            if($start_at->timestamp < $start_time->timestamp){
                $start_at = $start_time;
            }
            if($end_at->timestamp > $end_time->timestamp){
                $end_at = $end_time;
            }
            $statistic['creator_id'] = hashid_encode($statistic['creator_id']);
            $type = $statistic['type'];
            $minute = 0;
            for($day = $start_at->dayOfYear;$day<=$end_at->dayOfYear;$day++){
                if($start_at->dayOfYear == $day){//第一天
                    $minute = Carbon::tomorrow()->diffInMinutes($start_at);//加班时长
                }elseif($end_at->dayOfYear == $day){//最后一天
                    $minute = $end_at->diffInMinutes(Carbon::create($end_at->year,$end_at->month,$end_at->day,0,0,0));
                }else{
                    $minute = 8*60;
                }

                $creator_id_arr = array_column($daynumber,'creator_id');
                $creator_key = array_search($statistic['creator_id'],$creator_id_arr);
                if($creator_key >= 0 && $creator_key !== false){
                    $type_arr = array_column($daynumber[$creator_key]['daynumber'],'type');
                    $type_key = array_search($type,$type_arr);
                    if($type_key>=0 && $type_key!==false){
                        $daynumber[$creator_key]['daynumber'][$type_key]['number'] += self::computeDay($minute);
                    }else{
                        $daynumber[$creator_key]['daynumber'][] = [
                            'type' => $type,
                            'number'    =>  self::computeDay($minute)
                        ];

                    }
                }else{
                    $daynumber[]=[
                        'creator_id'    =>  $statistic['creator_id'],
                        'daynumber' =>  [
                            [
                                'type'  =>  $type,
                                'number'    =>  self::computeDay($minute)
                            ]
                        ]
                    ];
                }
            }
        }
        return ["data"  =>  $daynumber];
    }

    /**
     * 成员考勤--请假统计
     * @param $department  组织架构ID
     * @param $start_time  开始时间
     * @param $end_time    结束时间
     */
    public static function leaveStatistics($department,$start_time,$end_time){
        $where = [];
        if(!is_null($start_time)){
            $where[] = ['end_at','>',$start_time];
        }
        if(!is_null($end_time)){
            $where[] = ['start_at','<',$end_time];
        }
        $start_time = Carbon::parse($start_time);
        $end_time = Carbon::parse($end_time);

//        DB::connection()->enableQueryLog();
        //获取要查询部门的子级部门
        $departments_list = (new Department())->getSubidByPid($department);
        $department_id_list = array_column($departments_list,'id');
        array_push($department_id_list,$department);
        $leaveStatistics = (new Attendance())
            ->setTable('a')
            ->from('attendances as a')
            ->leftJoin('department_user as d','a.creator_id','=','d.user_id')
            ->leftJoin('users as u','u.id','=','a.creator_id')
            ->where($where)

            ->whereIn('d.department_id',$department_id_list)
            ->where('a.type',Attendance::LEAVE)//请假
            ->get(
                [
                    'creator_id',
                    'u.icon_url',
                    'u.name',
                    'a.type',
                    'start_at',
                    'end_at',
                    'a.leave_type',
                    DB::raw(//申请类型 1:请假 2:加班 3:出差 4:外勤
                        "case a.type
                        when 1 then '请假'
                        when 2 then '加班'
                        when 3 then '出差'
                        when 4 then '外勤'
                        else '数据错误'
                        end type_name"
                    ),
                    DB::raw(
                        "case a.leave_type
                        when 1 then '事假'
                        when 2 then '病假'
                        when 3 then '调休假'
                        when 4 then '年假'
                        when 5 then '婚假'
                        when 6 then '产假'
                        when 7 then '陪产假'
                        when 8 then '丧假'
                        when 9 then '其他'
                        else null end leave_type"
                    )
                ]
            );
//        $log = DB::getQueryLog();
//        var_dump($log);
        $daynumber = [];
        foreach ($leaveStatistics as $leaveStatistic){
            $leave_type = $leaveStatistic['leave_type'];
            $start_at = Carbon::parse($leaveStatistic['start_at']);
            $end_at = Carbon::parse($leaveStatistic['end_at']);
            $leaveStatistic['creator_id'] = hashid_encode($leaveStatistic['creator_id']);
            if($start_at->timestamp < $start_time->timestamp){
                $start_at = $start_time;
            }
            if($end_at->timestamp > $end_time->timestamp){
                $end_at = $end_time;
            }
            for($day = $start_at->dayOfYear;$day<=$end_at->dayOfYear;$day++){
                if ($start_at->dayOfYear == $day) {//第一天
                    $minute = Carbon::tomorrow()->diffInMinutes($start_at);//加班时长
                } elseif ($end_at->dayOfYear == $day) {//最后一天
                    $minute = $end_at->diffInMinutes(Carbon::create($end_at->year, $end_at->month, $end_at->day, 0, 0, 0));
                } else {
                    $minute = 8*60;
                }

                $creator_id_arr = array_column($daynumber,'creator_id');
                $creator_key = array_search($leaveStatistic['creator_id'],$creator_id_arr);
                if($creator_key >= 0 && $creator_key !== false){
                    $leave_type_arr = array_column($daynumber[$creator_key]['daynumber'],'leave_type');
                    $leave_type_key = array_search($leave_type,$leave_type_arr);
                    if($leave_type_key>=0 && $leave_type_key!==false){
                        $daynumber[$creator_key]['daynumber'][$leave_type_key]['number'] += self::computeDay($minute);
                    }else{
                        $daynumber[$creator_key]['daynumber'][] = [
                            'leave_type' => $leave_type,
                            'number'    =>  self::computeDay($minute)
                        ];

                    }
                }else{
                    $daynumber[]=[
                        'creator_id'    =>  $leaveStatistic['creator_id'],
                        'daynumber' =>  [
                            [
                                'leave_type'  =>  $leave_type,
                                'number'    =>  self::computeDay($minute)
                            ]
                        ]
                    ];
                }
            }
//            $daynumber[$leaveStatistic['creator_id']][$leaveStatistic['leave_type']] = $leaveStatistic;

        }
        return $daynumber;
    }

    /**
     * 考勤汇总--请假
     * @param $department
     * @param $start_time
     * @param $end_time
     * @type 1:请假  2:加班 3:出差  4:外勤
     * @return mixed
     */
    public static function collect($department,$start_time,$end_time,$type){
        $where = [];
        if(!is_null($start_time)){
            $where[] = ['end_at','>',$start_time];
        }
        if(!is_null($end_time)){
            $where[] = ['start_at','<',$end_time];
        }
        $field = [
            'a.creator_id',//用户ID
            'u.name',//姓名
            'u.icon_url',//头像
            'd.name',//所属部门名称
            'a.number',
            'a.start_at',
            'a.end_at',
            'a.approval_flow',//todo 审批人,审批流暂时不知道咋弄
            'a.type',
            DB::raw(//申请类型 1:请假 2:加班 3:出差 4:外勤
                "case a.type
                        when 1 then '请假'
                        when 2 then '加班'
                        when 3 then '出差'
                        when 4 then '外勤'
                        else '数据错误'
                        end type_name"
            ),
        ];
        if($type == 1){
            $field[] = DB::raw(
                "case leave_type
                when 1 then '事假'
                when 2 then '病假'
                when 3 then '调休假'
                when 4 then '年假'
                when 5 then '婚假'
                when 6 then '产假'
                when 7 then '陪产假'
                when 8 then '丧假'
                when 9 then '其他'
                else null end leave_type"
            );
        }
//        DB::connection()->enableQueryLog();
        //获取要查询部门的子级部门
        $departments_list = (new Department())->getSubidByPid($department);
        $department_id_list = array_column($departments_list,'id');
        array_push($department_id_list,$department);
        $start_time = Carbon::parse($start_time);
        $end_time = Carbon::parse($end_time);
        $leavecollect = (new Attendance())
                        ->setTable('a')
                        ->from('attendances as a')
                        ->leftJoin('department_user as du','a.creator_id','=','du.user_id')
                        ->leftjoin('departments as d','d.id','=','du.department_id')
                        ->leftJoin('users as u','u.id','=','a.creator_id')
                        ->where($where)
                        ->whereIn('d.id',$department_id_list)
                        ->where('a.type',$type)
                        ->get(
                            $field
                        );
        foreach ($leavecollect as &$value){
            $start_at = Carbon::parse($value['start_at']);
            $end_at = Carbon::parse($value['end_at']);
            if($start_at->timestamp < $start_time->timestamp){
                $start_at = $start_time;
            }
            if($end_at->timestamp > $end_time->timestamp){
                $end_at = $end_time;
            }
            for($day = $start_at->dayOfYear;$day<=$end_at->dayOfYear;$day++){
                if($start_at->dayOfYear == $day){//第一天
                    $minute = Carbon::tomorrow()->diffInMinutes($start_at);//加班时长
                    if(!isset($value['daynumber'])){
                        $value['daynumber'] = self::computeDay($minute);
                    }else{
                        $value['daynumber'] += self::computeDay($minute);
                    }
                }elseif($end_at->dayOfYear == $day){//最后一天
                    $minute = $end_at->diffInMinutes(Carbon::create($end_at->year,$end_at->month,$end_at->day,0,0,0));
                    if(!isset($value['daynumber'])){
                        $value['daynumber'] = self::computeDay($minute);
                    }else{
                        $value['daynumber'] += self::computeDay($minute);
                    }
                }else{
                    if(!isset($value['daynumber'])){
                        $value['daynumber'] = 1;
                    }else{
                        $value['daynumber'] += 1;
                    }
                }
            }

        }
        return $leavecollect;
    }

    /**
     * 考勤日历
     * @param $start_day
     * @param $end_day
     */
    public static function attendanceCalendar($start_time,$end_time)
    {
        $field = [
            'a.start_at',
            'a.end_at',
            'a.number',//请假时长
            'u.name',
            'a.type',
            'a.creator_id',
            DB::raw(//申请类型 1:请假 2:加班 3:出差 4:外勤
                "case a.type
                        when 1 then '请假'
                        when 2 then '加班'
                        when 3 then '出差'
                        when 4 then '外勤'
                        else '数据错误'
                        end type_name"
            ),
            //如果开始时间小于等于查询的开始时间，那么请假时间还剩 结束时间减去开始时间
            //如果开始时间大于查询开始时间，那么请假剩余时间是请假时长
            DB::raw("CASE (start_at <= '{$start_time}')
                                WHEN  1 THEN end_at - '{$start_time}'
                                WHEN  0 THEN a.number
                                ELSE
                                    '数据错误'
                            END remaining_time"
            )
        ];
        $attendanceCalendar = (new Attendance())
                                ->setTable('a')
                                ->from('attendances as a')
                                ->leftJoin('users as u','u.id','=','a.creator_id')
                                ->where([
                                   ['end_at','>',$start_time],
                                   ['start_at','<',$end_time],
                                ])->get($field);
        $start_time = Carbon::parse($start_time);
        $end_time = Carbon::parse($end_time);
        $list = [];
        foreach ($attendanceCalendar as $value){
            $start_at = Carbon::parse($value['start_at']);
            $end_at = Carbon::parse($value['end_at']);
            $value['creator_id'] = hashid_encode($value['creator_id']);
            if($start_at->timestamp < $start_time->timestamp){
                $start_at = $start_time;
            }
            if($end_at->timestamp > $end_time->timestamp){
                $end_at = $end_time;
            }
            for($day = $start_at->dayOfYear;$day<=$end_at->dayOfYear;$day++) {
                $date = $start_at->copy()->addDay($day-$start_at->dayOfYear);
                $date = $date->toDateString();
                $minute = 0;
                if ($start_at->dayOfYear == $day) {//第一天
                    $minute = Carbon::tomorrow()->diffInMinutes($start_at);//加班时长
//                    $value['now'] = self::computeDay($minute);//当天加班时长
//                    if ($value['daynumber']) {
//                        $value['daynumber'] = self::computeDay($minute);
//                    } else {
//                        $value['daynumber'] += self::computeDay($minute);
//                    }
                } elseif ($end_at->dayOfYear == $day) {//最后一天
                    $minute = $end_at->diffInMinutes(Carbon::create($end_at->year, $end_at->month, $end_at->day, 0, 0, 0));
//                    $value['now'] = self::computeDay($minute);//当天加班时长
//                    if (!isset($value['daynumber'])) {
//                        $value['daynumber'] = self::computeDay($minute);
//                    } else {
//                        $value['daynumber'] += self::computeDay($minute);
//                    }
                } else {
                    $minute = 8 * 60;
//                    $value['now'] = 1;//当天加班时长
//                    if (!isset($value['daynumber'])) {
//                        $value['daynumber'] = 1;
//                    } else {
//                        $value['daynumber'] += 1;
//                    }
                }
                $date_arr = array_column($list,'date');
                $date_key = array_search($date,$date_arr);
                if($date_key >= 0 && $date_key !== false){
                    $creator_id_arr = array_column($list[$date_key]['creators'],'creator_id');
                    $creator_id_key = array_search($value['creator_id'],$creator_id_arr);
                    if($creator_id_key >= 0 && $creator_id_key !== false){
                        $type_arr = array_column($list[$date_key]['creators'][$creator_id_key]['daynumber'],'type');
                        $type_key = array_search($value['type'],$type_arr);
                        if($type_key >=0 && $type_key !== false){
                            $list[$date_key]['creators'][$creator_id_key]['daynumber'][$type_key]['number'] += self::computeDay($minute);
                        }else{
                            $list[$date_key]['creators'][$creator_id_key]['daynumber'][] = [
                                'type'  =>  $value['type'],
                                'number'    =>  self::computeDay($minute)
                            ];
                        }
                    } else{
                        $list[$date_key]['creators'][] = [
                            'creator_id'    =>  $value['creator_id'],
                            'daynumber' =>  [
                                [
                                    'type'    =>  $value['type'],
                                    'number'  =>  self::computeDay($minute),
                                ]

                            ]
                        ];
                    }
                }else{
                    $list[] = [
                        'date'  =>  $date,
                        'creators'  =>  [
                            [
                                'creator_id'    =>  $value['creator_id'],
                                'daynumber' =>  [
                                    [
                                        'type'  =>  $value['type'],
                                        'number'    =>  self::computeDay($minute)
                                    ]
                                ]
                            ]
                        ]
                    ];
                }

//                $list[$date][$value['creator_id']][$value['type']] = $value;
            }

        }
        return $list;
    }

    /**
     * 查询用户申请
     */
    public static function myApply($creator_id,$status)
    {
        $myApplyList = (new Attendance())
            ->where('creator_id',$creator_id)
            ->where('status',$status)
            ->get([
                'start_at',
                'end_at',
                'number',
                DB::raw("case status
                when 1 then '待审批'
                when 2 then '已同意'
                when 3 then '已拒绝'
                when 4 then '已作废'
                else '数据错误'
                end status
                ")
            ]);
        return $myApplyList;
    }
}
