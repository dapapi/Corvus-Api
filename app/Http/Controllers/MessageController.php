<?php

namespace App\Http\Controllers;

use App\Http\Transformers\MessageTransform;
use App\Models\Message;
use App\Models\MessageState;
use App\ModuleableType;
use App\Repositories\MessageRepository;
use Carbon\Carbon;
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
            $arr[]  =   ['ms.state',$state];
        }
        $user = Auth::guard('api')->user();
        $arr[] = ['ms.user_id',$user->id];
        $result = (new Message())->setTable("m")->from('messages as m')
            ->leftJoin('message_states as ms','ms.message_id','m.id')
            ->leftJoin('message_datas as md','md.message_id','ms.message_id')
            ->orderBy('m.created_at','desc')
            ->select(
                'm.id','m.module','m.title as message_title','m.link','m.created_at',
                'ms.user_id','md.title','md.value','ms.state'
                )
            ->where($arr)
            ->get();
        $list = [];
        $no_read = 0;//未读消息数量
        foreach ($result->toArray() as $value){
            $value['created'] = Carbon::parse($value['created_at'])->format('Y-m-d');
            if(!isset($list[$value['created']])){
                if($value['state'] == MessageState::UN_READ){
                    $no_read++;
                }
                $list[$value['created']][] = [
                    'message_id' => $value['id'],
                    'message_title'=> $value['message_title'],
                    'link'=> $value['link'],
                    'created_at' => $value['created_at'],
                    'created' => Carbon::parse($value['created_at'])->format('Y-m-d'),
                    'dayofweek' => Carbon::parse($value['created_at'])->dayOfWeek,
                    'module'   =>   $value['module'],
                    'state' =>  $value['state'],
                    'body'=>[['title'=>$value['title'],'value'=>$value['value']]],
                ];
            }else{
                $message_key = array_search($value['id'],array_column($list[$value['created']],'message_id'));
                if($message_key === false){
                    if($value['state'] == MessageState::UN_READ){
                        $no_read++;
                    }
                    $list[$value['created']][] = [
                        'message_id' => $value['id'],
                        'message_title'=> $value['message_title'],
                        'link'=> $value['link'],
                        'created_at' => $value['created_at'],
                        'created' => Carbon::parse($value['created_at'])->format('Y-m-d'),
                        'dayofweek' => Carbon::parse($value['created_at'])->dayOfWeek,
                        'module'   =>   $value['module'],
                        'state' =>  $value['state'],
                        'body'=>[['title'=>$value['title'],'value'=>$value['value']]],
                    ];
                }else{
                    $list[$value['created']][$message_key]['body'][] = ['title'=>$value['title'],'value'=>$value['value']];
                }

            }

        }
        return [
            'no_read'=>$no_read,
            'data'=>$list
        ];
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
    public function checklogin(){

    }

}
