<?php

namespace App\Http\Controllers;

use App\Events\OperateLogEvent;
use App\Http\Requests\AttendanceRequest;
use App\Http\Requests\AttendancesStatisticsRepository;
use App\Http\Requests\AttendanceStatisticsRequest;
use App\Http\Transformers\AttendanceTransformer;
use App\Models\Attendance;
use App\Models\OperateEntity;
use App\OperateLogMethod;
use App\Repositories\AttendanceRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;

//考勤
class AttendanceController extends Controller
{
    public function index()
    {

    }

    /**
     * 存储考勤
     * @param AttendanceRequest $request
     * @param Attendance $attendance
     */
    public function store(AttendanceRequest $request)
    {
        $payload = $request->all();
        $user = Auth::guard('api')->user();
        unset($payload['creator_id']);
        $payload['creator_id']  =   $user->id;
        //暂时存疑,应该判断审批流是否存在
        $payload['approval_flow']   = hashid_decode($payload['approval_flow']);

        DB::beginTransaction();
        try{
            $attendance = Attendance::create($payload);
            // 操作日志
            $operate = new OperateEntity([
                'obj' => $attendance,
                'title' => null,
                'start' => null,
                'end' => null,
                'method' => OperateLogMethod::CREATE,
            ]);
            event(new OperateLogEvent([
                $operate,
            ]));
            //还有附件没写
        }catch (Exception $e){
            DB::rollBack();
            Log::error($e);
            $this->response->errorInternal("申请创建失败");
        }
        DB::commit();
        return $this->response->item(Attendance::find($attendance->id),new AttendanceTransformer());
    }

    /**
     * 统计当前登陆用户的考勤
     * @return \Dingo\Api\Http\Response
     */
    public function myselfStatistics()
    {
        $user = Auth::guard('api')->user();
        $myselfAttendance = AttendanceRepository::MyselfStatistics($user);
        return $this->response->collection($myselfAttendance,new AttendanceTransformer());
//        return $myselfAttendance;
    }

    /**
     * 统计当前用户的请假
     * @return array
     */
    public function myselfLeavelStatistics()
    {
        $user = Auth::guard('api')->user();
        $myselfLeavelStatistics = AttendanceRepository::myselfLeavelStatistics($user);
//        return $myselfLeavelStatistics;
        return $this->response->collection($myselfLeavelStatistics,new AttendanceTransformer());
    }

    /**
     * 根据条件查询考勤统计
     * @param Request $request
     */
    public function statistics(AttendanceStatisticsRequest $request)
    {
        $start_time = $request->get('start_time',null);
        $end_time = $request->get('end_time',null);
        $department = $request->get('department',null);
        $statistics = AttendanceRepository::statistics($department,$start_time,$end_time);
        return $this->response->collection($statistics,new AttendanceTransformer());
    }

    /**
     * 成员考勤--请假统计
     * @param Request $request
     * @return mixed
     */
    public function leavestatistics(Request $request)
    {
        $start_time = $request->get('start_time',null);
        $end_time = $request->get('end_time',null);
        $department = $request->get('department',null);
        $leavestatistics = AttendanceRepository::leaveStatistics($department,$start_time,$end_time);
        return $this->response->collection($leavestatistics,new AttendanceTransformer());
    }

    /**
     * 考勤汇总--请假
     * @param Request $request
     * @return mixed
     */
    public function collect(Request $request)
    {
        $start_time = $request->get('start_time',null);
        $end_time = $request->get('end_time',null);
        $department = $request->get('department',null);
        $type = $request->get('type',null);
        $leavecollect = AttendanceRepository::collect($department,$start_time,$end_time,$type);
        return $this->response->collection($leavecollect,new AttendanceTransformer());
    }

    /**
     * 考勤日历
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function attendanceCalendar(Request $request){
        $start_time = $request->get('start_time',null);
        $end_time = $request->get('end_time',null);
        $attendanceCalendar = AttendanceRepository::attendanceCalendar($start_time,$end_time);
        return $attendanceCalendar;
        return $this->response->collection($attendanceCalendar,new AttendanceTransformer());
    }

    public function myApply(Request $request){
        $status = $request->get("status",null);
        $user = Auth::guard('api')->user();
        $myapply = AttendanceRepository::myApply($user->id,$status);
        return $this->response->collection($myapply,new AttendanceTransformer());
    }


}
