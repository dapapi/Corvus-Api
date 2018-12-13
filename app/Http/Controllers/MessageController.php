<?php

namespace App\Http\Controllers;

use App\Http\Transformers\MessageTransform;
use App\Models\Message;
use App\Models\MessageState;
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
            $arr[] = ['module', $module];
        }
        if($state != null){
            $arr[]  =   ['state',$state];
        }
//        $user = Auth::guard('api')->user();
//        $arr[] = ['user_id',$user->id];
        $pageSize = $request->get('page_size', config('app.page_size'));
        $result = Message::where($arr)->paginate($pageSize);
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
}
