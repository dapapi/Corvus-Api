<?php

namespace App\Http\Controllers;

use App\Http\Transformers\MessageTransform;
use App\Models\Message;
use App\Models\MessageState;
use App\ModuleableType;
use App\Repositories\MessageRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $module = $request->get('module',null);
        $state = $request->get('state',null);
        $arr = [];
        if($module != null){
            $arr[] = ['m.module', $module];
        }
        if($state != null){
            $arr[]  =   ['m.state',$state];
        }
        $user = Auth::guard('api')->user();
        $arr[] = ['ms.user_id',$user->id];
        $pageSize = $request->get('page_size', config('app.page_size'));
        $result = (new Message())->setTable("m")->from('messages as m')
            ->leftJoin('message_states as ms','ms.message_id','m.id')
            ->groupBy('m.id')
            ->select('m.id','m.module','m.title','m.link')
            ->where($arr)->paginate($pageSize);
        return $this->response()->paginator($result,new MessageTransform());
    }
    //设置已读未读状态
    public function changeSate(Request $request){
        $message_id = $request->get('message_id',null);
        $user = Auth::guard('api')->user();
        try{
            $message_sate = MessageState::findOrFail(['message_id' => hashid_decode($message_id),'user_id'=>$user->id]);
            $message_sate->state = MessageState::HAS_READ;//已读
        }catch (\Exception $e){
            $this->response()->errorInternal("消息不存在");
        }
        return $this->response()->noContent();
    }
    public function getModules()
    {
        return [
            ModuleableType::PROJECT => '项目',
        ModuleableType::TASK => '任务',
        ModuleableType::STAR => '艺人',
        ModuleableType::CLIENT => '客户',
        ModuleableType::CONTACT => '联系人',
        ModuleableType::TRAIL => '线索',
        ModuleableType::BLOGGER => '博主',
        ModuleableType::USER => '用户',
        ModuleableType::PERSONA_JOB => '人事',
        ModuleableType::PERSONA_SALARY => '工资',
        ModuleableType::WORK => '作品库',
        ModuleableType::ATTENDANCE => '考勤',
        ModuleableType::CALENDAR => '日历',
        ModuleableType::SCHEDULE => '调度',
        ModuleableType::ANNOUNCEMENT => '公告',
        ModuleableType::ISSUES => '问题',
        ModuleableType::REPORT => '报告',
        ModuleableType::DEPARTMENT => '部门',
        ModuleableType::GTOUPROLES => '组',
        ModuleableType::ROLE => '角色',
        ];
    }

}
