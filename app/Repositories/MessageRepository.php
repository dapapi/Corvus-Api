<?php

namespace App\Repositories;

use App\Helper\SendMessage;
use App\Models\Message;
use App\Models\MessageData;
use App\Models\MessageState;
use App\User;
use Carbon\Carbon;
use function foo\func;
use Illuminate\Support\Facades\DB;

class MessageRepository
{
    //向数据库添加消息，向前端推消息
    public function addMessage($user,$authorization,$title,$subheading,$module,$link,$data,$recives){

        $message = new Message();
        $message->title = $title;
        $message->subheading = $subheading;
        $message->module = $module;
        $message->link = $link;
        $message->save();

        foreach ($data as &$value){
            $value['message_id'] = $message->id;
        }

        DB::table('message_datas')->insert($data);
        //todo 消息发送对列优化
        $recives_data = [];
        if ($recives == null){//如果recives为null表示全员接收
            $recives = User::select('id')->get()->toArray();

        }
        $recives = array_unique($recives);//去重
        foreach ($recives as $recive){
            $recives_data[] = [
                'message_id'  =>  $message->id,
                'user_id' =>  $recive,
                'created_at'    =>  Carbon::now()
            ];
        }




//        $message_state = new MessageState();
        DB::table("message_states")->insert($recives_data);
        $send_message = new SendMessage();
        foreach ($recives as &$recive){
            $recive = hashid_encode($recive);
        }
        $send_message->login($authorization,$user->id,$user->name,$title,$subheading,$link,$data,$recives);

//        $send_message->sendMessage($title,$subheading,$link,$data,$recives);
    }

}
