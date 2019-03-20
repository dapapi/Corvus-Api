<?php

namespace App\Repositories;

use App\Helper\SendMessage;
use App\Models\DataDictionarie;
use App\Models\Message;
use App\Models\MessageData;
use App\Models\MessageState;
use App\User;
use Carbon\Carbon;
use function foo\func;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageRepository
{
    //向数据库添加消息，向前端推消息
    public function addMessage($user,$authorization,$title,$subheading,$module,$link,$data,$recives,$module_data_id){
        $message = new Message();
        $message->title = $title;
        $message->subheading = $subheading;
        $message->module = $module;
        $message->link = $link;
        if ($module == Message::APPROVAL||$module == Message::CONTRACT){
            $message->module_data_id = $module_data_id;
        }else{
            $message->module_data_id    =  hashid_encode($module_data_id);
        }
        $message->save();
        foreach ($data as &$value){
            $value['message_id'] = $message->id;
        }

        DB::table('message_datas')->insert($data);
        //todo 消息发送对列优化
        $recives_data = [];
        if ($recives === null){//如果recives为null表示全员接收
            $recives = array_column(User::select('id')->where('id','!=',$user->id)->get()->toArray(),'id');
        }
        $recives = array_unique($recives);//去重
        foreach ($recives as $key => $recive){
            if ($recive != $user->id){
                $recives_data[] = [
                    'message_id'  =>  $message->id,
                    'user_id' =>  $recive,
                    'created_at'    =>  Carbon::now()->toDateTimeString(),
                ];
            }else{
                unset($recives[$key]);
            }


        }


//        $message_state = new MessageState();
        DB::table("message_states")->insert($recives_data);
        $send_message = new SendMessage();
        foreach ($recives as &$recive){
            $recive = hashid_encode($recive);
        }
        Log::info("向".implode(",",$recives)."发消息");
        $send_message->login($authorization,$user->id,$user->name,$title,$subheading,$link,$data,$recives);
        Log::info("发送完毕...");
    }

    /**
     * 获取用户消息条数
     * @param $user_id
     * @param null $state
     * @return mixed
     */
    public function getUnMessageNum($user_id,$module,$state=null)
    {
        $query = Message::where('ms.user_id',$user_id)->where("messages.module",$module)
            ->leftJoin('message_states as ms',"ms.message_id","messages.id")
        ;
        if ($state){
            $query->where('state',$state);
        }
        return $query->count();
    }
    //获取所有消息模块
    public function getModules()
    {
        return DataDictionarie::select("id","val","name","icon")->where("parent_id",206)->get()->toArray();
    }
    //获取用户某模块最新消息
    public function getLastNewsByModule($module,$user_id)
    {
        return Message::where("messages.module",$module)->leftJoin("message_states as ms","ms.message_id","messages.id")
            ->where("ms.user_id",$user_id)->select('messages.title','ms.state',"ms.created_at")->orderBy('messages.created_at','desc')->first();
    }
    //获取用户消息
    public function getMessageList($module,$user_id,$state)
    {
        $arr = [];
        if($module != null){
            $arr[] = ['messages.module', $module];
        }
        if($state != null){
            $arr[]  =   ['ms.state',$state];
        }
        return Message::
            leftJoin("message_states as ms","ms.message_id","messages.id")
            ->where($arr)
            ->where("ms.user_id",$user_id)
            ->orderBy('messages.created_at','desc')
            ->select('messages.id','messages.created_at','messages.module','messages.title','ms.state',"messages.module_data_id");

    }

}
