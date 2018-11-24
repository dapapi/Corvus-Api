<?php

namespace App\Repositories;


use App\Models\Attendance;
use App\Models\Users;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
     *
     *
     * @param $start_time  开始时间
     * @param $end_time  结束时间
     */
    public static function attendanceRule($start_time,$end_time,$type)
    {
        $day_number = 0;//考勤天数
        $eight_hour_second = env("eight_hour_second");
        $one_hour_second = env("ONE_HOUR_SECOND");
        $start = Carbon::parse($start_time);
        $end = Carbon::parse($end_time);
        //计算请假开始时间和结束时间是否是同一天
        if($start->year == $end->year && $start->dayOfYear == $end->dayOfYear){
            $temp_second = $end->second - $start->second;
            if($temp_second > $eight_hour_second){//加班大于8小时算1天
                return 1;
            }else{//如果小于八小时
                $seconds = $temp_second % $one_hour_second;//
                $day_number = self::computeDayNumber($seconds);
                $hours = intval($temp_second / $one_hour_second);
                $day_number += $hours / 8;
                return $day_number;
            }
        }
        //计算请假开始时间和结束时间不是同一天,但是是同一个月
        if ($start->year == $end->year && $start->month == $end->month && $start->dayOfYear != $end->dayOfYear){
            if($end->dayOfYear - $start->dayOfYear == 1){

            }
        }

    }

    /**
     * 计算请假，加班，外勤，出差，天数
     * @param $seconds
     */
    public function computeDayNumber($seconds)
    {

        $day_number = 0;
        $eight_hour_second = env("eight_hour_second");
        $half_hour_second = env("half_hour_second");
        $fifteen_second = env("FIFTEEN_SECOND");
        $one_hour_second = env("ONE_HOUR_SECOND");

        //小于半小时
        if($seconds < $half_hour_second)
        {
            return 0;
        }
        //小于半小时 大于45分
        if($seconds >= $half_hour_second && $seconds < ($half_hour_second + $fifteen_second))
        {
            return 0.5/8;
        }
        //大于45分小于1小时
        if($seconds >= ($half_hour_second+$fifteen_second) && $one_hour_second)
        {
            return 1/8;
        }
    }

    public static function condition(array $array)
    {

    }
    public static function myselfStatistics(User $user,$year){
//        DB::connection()->enableQueryLog();
//        $myselfAttendance = Attendance::where('creator_id',$user->id)
//                                    ->whereRaw("year(start_at) = ?",[$year])
//                                    ->groupBy(DB::raw('month(start_at)'))
//                                    ->groupBy("type")
//                                    ->get(
//                                        [
//                                            DB::raw('month(start_at) AS \'MONTH\'' ),
//                                            DB::raw('case type=1 when  1 then sum(number) end \'LEAVE\''),
//                                            DB::raw('case type=2 when  1 then sum(number) end \'OVERTIME\''),
//                                            DB::raw('case type=3 when  1 then sum(number) end \'BUSINESS_TRAVEL\''),
//                                            DB::raw('case type=4 when  1 then sum(number) end \'FIELD_OPERATION\''),
//                                            DB::raw('MONTH(start_at)'),
//                                        ]
//                                    );
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
            'number'
        ]);
//        dd($myselfAttendance);
        $daynumber = [];
        foreach ($myselfAttendance as $attendance){
            $start_at = $attendance['start_at'];
            $end_at = $attendance['end_at'];
            $type = $attendance['type'];
            $number = $attendance['number'];
            if($start_at < $start_year){
                $start_at = $start_year;
            }
            if($end_at > $end_year){
                $end_at = $end_year;
            }
            $start_time = Carbon::parse($end_at);
            $end_time = Carbon::parse($start_at);
            //在同一个月
            if($start_time->month == $end_time->month){
                $daynumber[$start_time->month][$type] = $number;
            }else{//不在同一个月
                //不在同一个月但是两个月相邻
                if($end_time->month - $start_time->month == 1){
                    $first_month_end_time = Carbon::create($year,$start_time->month,$start_time->lastOfMonth(),23,59,59);
                    $last_month_start_time = Carbon::create($year,$end_time->month,$end_time->firstOfMonth(),0,0,0);

                }
            }



        }
        dd($myselfAttendance);
//        $sql = DB::getQueryLog();
//        dd($sql);
        return $myselfAttendance;
    }

    public static function myselfLeavelStatistics(User $user)
    {
        $myselfLeavelStatistics = DB::select(
            "SELECT MONTH(start_at) AS MONTH,
				SUM(leave_type=1 OR NULL) AS 'CASUAL_LEAVE',
				SUM(leave_type=2 OR NULL) AS 'SICK_LEAVE',
				SUM(leave_type=3 OR NULL) AS 'LEAVE_IN_LIEU',
				SUM(leave_type=4 OR NULL) AS 'ANNUAL_LEAVE',
				SUM(leave_type=5 OR NULL)	AS 'MARRIAGE_LEAVE',
				SUM(leave_type=6 OR NULL) AS 'MATERNITY_LEAVE',
				SUM(leave_type=7 OR NULL) AS 'PATERNITY_LEAVE',
				SUM(leave_type=8 OR NULL) AS 'FUNERAL_LEAVE',
				SUM(leave_type=9 OR NULL) AS 'OTHER_LEAVE'
				SUM(leave_type OR NULL) AS 'SUM'
				FROM attendances where `type` = 1 AND `creator_id` = {$user->id} GROUP BY MONTH(start_at)"
        );
        return $myselfLeavelStatistics;
    }

    /**
     * 根据条件
     * @param $department 组织架构ID
     * @param $start_time 开始时间
     * @param $end_time   结束时间
     */
    public static function statistics($department,$start_time,$end_time)
    {
        $start_arr = [];//查询条件
        $end_arr = [];
        if($start_time != null && $end_time != null){
            $start_arr[] = ['start_at','>',$start_time];
            $start_arr[] = ['start_at','<',$end_time];
            $end_arr[] = ['end_at','>',$start_time];
            $end_arr[] = ['end_at','<',$end_time];
        }
        if($start_time != null && $end_time == null){
            $start_arr[] = ['start_at','>',$start_time];
            $end_arr[] = ['end_at','>',$start_time];
        }
        if($start_time == null && $end_time != null) {
            $start_arr[] = ['start_at', '<', $end_time];
            $end_arr[] = ['end_at', '<', $end_time];
        }
        $statistics = (new Attendance())
            ->setTable('a')
            ->from('attendances as a')
            ->leftJoin('department_user as d','a.creator_id','=','d.user_id')
            ->leftJoin('users as u','u.id','=','a.creator_id')
            ->where($start_arr)
            ->orWhere($end_arr)
            ->where('d.department_id','=',$department)
            ->groupBy('a.creator_id')
            ->get(
                [
                    'creator_id',
                    'u.icon_url',
                    'u.name',
                    DB::raw('sum(a.type=1 OR NULL) AS \'LEAVE\''),
                    DB::raw('sum(a.type=2 OR NULL) AS \'OVERTIME\''),
                    DB::raw('sum(a.type=3 OR NULL) AS \'BUSINESS_TRAVEL\''),
                    DB::raw('sum(a.type=4 OR NULL) AS \'FIELD_OPERATION\''),
                    DB::raw('sum(a.type OR NULL) AS \'SUM\'')
                ]
            );
//        $statistics = DB::select(
//          "SELECT
//                    a.creator_id,
//                    u.icon_url,
//                    u.`name`,
//                    MONTH(a.start_at) AS 'MONTH',
//                    SUM(a.type=1 OR NULL) AS 'LEAVE',
//                    SUM(a.type=2 OR NULL) AS 'OVERTIME',
//                    SUM(a.type=3 OR NULL) AS 'BUSINESS_TRAVEL',
//                    SUM(a.type=4 OR NULL) AS 'FIELD_OPERATION'
//                from attendances as a
//                LEFT JOIN department_user AS d ON a.creator_id = d.user_id
//                LEFT JOIN users as u ON u.id = a.creator_id
//                where (
//                        (start_at > '{$start_time}' and start_at < '{$end_time}')
//                        OR
//                        (end_at > '{$start_time}' and end_at < '{$end_time}')
//                      )
//                      AND department_id = {$department}
//                GROUP BY month(a.start_at)"
//        );
        return $statistics;
    }

    /**
     * 成员考勤--请假统计
     * @param $department  组织架构ID
     * @param $start_time  开始时间
     * @param $end_time    结束时间
     */
    public static function leaveStatistics($department,$start_time,$end_time){
        $start_arr = [];//查询条件
        $end_arr = [];
        if($start_time != null && $end_time != null){
            $start_arr[] = ['start_at','>',$start_time];
            $start_arr[] = ['start_at','<',$end_time];
            $end_arr[] = ['end_at','>',$start_time];
            $end_arr[] = ['end_at','<',$end_time];
        }
        if($start_time != null && $end_time == null){
            $start_arr[] = ['start_at','>',$start_time];
            $end_arr[] = ['end_at','>',$start_time];
        }
        if($start_time == null && $end_time != null) {
            $start_arr[] = ['start_at', '<', $end_time];
            $end_arr[] = ['end_at', '<', $end_time];
        }
//        DB::connection()->enableQueryLog();
        $leaveStatistics = (new Attendance())
            ->setTable('a')
            ->from('attendances as a')
            ->leftJoin('department_user as d','a.creator_id','=','d.user_id')
            ->leftJoin('users as u','u.id','=','a.creator_id')
            ->where(
                function ($query) use($start_arr,$end_arr){
                    $query->where($start_arr)
                        ->orWhere($end_arr);
                }
            )

            ->where('d.department_id','=',$department)
            ->where('a.type',1)
            ->groupBy('a.creator_id')
            ->get(
                [
                    DB::raw('SUM(leave_type=1 OR NULL) AS \'CASUAL_LEAVE\''),
                    DB::raw('SUM(leave_type=2 OR NULL) AS \'SICK_LEAVE\''),
                    DB::raw('SUM(leave_type=3 OR NULL) AS \'LEAVE_IN_LIEU\''),
                    DB::raw('SUM(leave_type=4 OR NULL) AS \'ANNUAL_LEAVE\''),
                    DB::raw('SUM(leave_type=5 OR NULL)	AS \'MARRIAGE_LEAVE\''),
                    DB::raw('SUM(leave_type=6 OR NULL) AS \'MATERNITY_LEAVE\''),
                    DB::raw('SUM(leave_type=7 OR NULL) AS \'PATERNITY_LEAVE\''),
                    DB::raw('SUM(leave_type=8 OR NULL) AS \'FUNERAL_LEAVE\''),
                    DB::raw('SUM(leave_type=9 OR NULL) AS \'OTHER_LEAVE\''),
                    DB::raw('SUM(leave_type OR NULL) AS \'SUM\'')
                ]
            );
//        $log = DB::getQueryLog();
//        var_dump($log);
        return $leaveStatistics;
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
        $start_arr = [];//查询条件
        $end_arr = [];
        if($start_time != null && $end_time != null){
            $start_arr[] = ['start_at','>',$start_time];
            $start_arr[] = ['start_at','<',$end_time];
            $end_arr[] = ['end_at','>',$start_time];
            $end_arr[] = ['end_at','<',$end_time];
        }
        if($start_time != null && $end_time == null){
            $start_arr[] = ['start_at','>',$start_time];
            $end_arr[] = ['end_at','>',$start_time];
        }
        if($start_time == null && $end_time != null) {
            $start_arr[] = ['start_at', '<', $end_time];
            $end_arr[] = ['end_at', '<', $end_time];
        }
        $field = [
            'u.id',//用户ID
            'u.name',//姓名
            'u.icon_url',//头像
            'd.name',//所属部门名称
            'a.number',
            'a.start_at',
            'a.end_at',
            'a.approval_flow'//审批人,审批流暂时不知道咋弄
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
        $leavecollect = (new Attendance())
                        ->setTable('a')
                        ->from('attendances as a')
                        ->leftJoin('department_user as du','a.creator_id','=','du.user_id')
                        ->leftjoin('departments as d','d.id','=','du.department_id')
                        ->leftJoin('users as u','u.id','=','a.creator_id')
                        ->where(
                            function ($query) use($start_arr,$end_arr){
                                $query->where($start_arr)
                                    ->orWhere($end_arr);
                            }
                        )
                        ->where('d.id','=',$department)
                        ->where('a.type',$type)
                        ->get(
                            $field
                        );
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
            'number',//请假时长
            'u.name',
            //如果开始时间小于等于查询的开始时间，那么请假时间还剩 结束时间减去开始时间
            //如果开始时间大于查询开始时间，那么请假剩余时间是请假时长
            DB::raw("CASE (start_at <= '2018-01-03 00:00:00')
                                WHEN  1 THEN end_at - '{$start_time}'
                                WHEN  0 THEN number
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
        return $attendanceCalendar;
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
