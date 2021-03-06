<?php

namespace App\Http\Controllers;

use App\Entity\PersonalDetailEntity;
use App\Http\Transformers\MessageTransform;
use App\Http\Transformers\MessageTransformer;
use App\Models\DataDictionarie;
use App\Models\Message;
use App\Models\MessageState;
use App\ModuleableType;
use App\Repositories\MessageRepository;
use Carbon\Carbon;
use function GuzzleHttp\Psr7\copy_to_string;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    private $messageReposirory;
    public function __construct(MessageRepository $messageReposirory)
    {
        $this->messageReposirory = $messageReposirory;
    }

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
                'm.id','m.module_data_id','m.module','m.title as message_title','m.link','m.created_at','m.subheading',
                'ms.user_id','md.title','md.value','ms.state'
                )
            ->where($arr)
            ->get();

        $list = [];
        $no_read = 0;//未读消息数量
        foreach ($result->toArray() as $value){
            $value['id']    =   hashid_encode($value['id']);
            $value['created'] = Carbon::parse($value['created_at'])->format('Y-m-d');
            if(!isset($list[$value['created']])){
                if($value['state'] == MessageState::UN_READ){
                    $no_read++;
                }
                $list[$value['created']][] = [
                    'message_id' => $value['id'],
                    'message_title'=> $value['message_title'],
                    'message_subheading'    =>  $value['subheading'],
                    'link'=> $value['link'],
                    'created_at' => $value['created_at'],
                    'created' => Carbon::parse($value['created_at'])->format('Y-m-d'),
                    'dayofweek' => Carbon::parse($value['created_at'])->dayOfWeek,
                    'module'   =>   $value['module'],
                    'module_data_id'    =>  $value['module_data_id'],
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
                        'message_subheading'    =>  $value['subheading'],
                        'link'=> $value['link'],
                        'created_at' => $value['created_at'],
                        'created' => Carbon::parse($value['created_at'])->format('Y-m-d'),
                        'dayofweek' => Carbon::parse($value['created_at'])->dayOfWeek,
                        'module'   =>   $value['module'],
                        'module_data_id'    =>  $value['module_data_id'],
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
        $all_read = $request->get('all','no');
        $module = $request->get("module",null);
        $user = Auth::guard('api')->user();
//        (new Message())->where('module',$module)->recive()->where('user_id',$user->id) ->update(['ms.state'=>MessageState::HAS_READ]);
        if($all_read=="yes" && $module != null && is_numeric($module)){
            $message = new Message();
            $message->timestamps = false;//禁用时间戳自动维护
            $message->setTable("m")->from("messages as m")
                ->leftJoin("message_states as ms",'ms.message_id','m.id')
                ->where([['ms.user_id',$user->id],['m.module',$module],['ms.state',MessageState::UN_READ]])
                ->update(['ms.state'=>MessageState::HAS_READ,"ms.updated_at"=>Carbon::now(),"updated_by"=>$user->name]);
        }
        if($message_id != null && $all_read == "no"){
            try{
                MessageState::where(['message_id' => hashid_decode($message_id),'user_id'=>$user->id])->update(['state'=>MessageState::HAS_READ,'updated_by'=>$user->name]);
            }catch (\Exception $e){
                $this->response()->errorInternal("修改失败");
            }
        }

        return $this->response()->noContent();
    }
    public function getModules(Request $request)
    {
        $user = Auth::guard('api')->user();
        $messageRepository = new MessageRepository();
        $modules = $messageRepository->getModules();
        foreach ($modules as &$module){
            //获取某块对应的用户未读消息
            $un_read = $messageRepository->getUnMessageNum($user->id,$module['id'],Message::UN_READ);
            //获取模块对应的用户最新消息
            $lastMessage = $messageRepository->getLastNewsByModule($module['id'],$user->id);
            $module['unread'] = $un_read;
            $module['laset_mesage'] = $lastMessage;
        }
        return ["data" => $modules];
    }

    public function MobileGetMessage(Request $request)
    {
        $module = $request->get('module',null);
        $state = $request->get('state',null);
        $user = Auth::guard('api')->user();
        $pageSize = $request->get('page_size', config('app.page_size'));
        //获取消息
        $message = $this->messageReposirory->getMessageList($module,$user->id,$state)->paginate($pageSize);
        return $this->response->paginator($message,new MessageTransformer());

    }

}
