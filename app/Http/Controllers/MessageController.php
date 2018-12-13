<?php

namespace App\Http\Controllers;

use App\Http\Transformers\MessageTransform;
use App\Models\Message;
use App\Models\MessageState;
use App\Repositories\MessageRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $pageSize = $request->get('page_size', config('app.page_size'));
        $result = Message::where($arr)->paginate($pageSize);
        return $this->response()->paginator($result,new MessageTransform());
    }
    //设置已读未读状态
    public function changeSate(Request $request){
        $id = $request->get('id',null);
        try{
            $message_sate = MessageState::findOrFail(hashid_decode($id));
            $message_sate->state = MessageState::HAS_READ;//已读
        }catch (\Exception $e){
            $this->response()->errorInternal("消息不存在");
        }
        return $this->response()->noContent();
    }
}
