<?php

namespace App\Repositories;

use App\Helper\SendMessage;
use App\Models\Message;
use App\Models\MessageData;
use App\Models\MessageState;
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
        $recives_data = [];
        foreach ($recives as $recive){
            $recives_data[] = [
                'message_id'  =>  $message->id,
                'user_id' =>  hashid_decode($recive),
                'created_at'    =>  Carbon::now()
            ];
        }

//        $message_state = new MessageState();
        DB::table("message_states")->insert($recives_data);

        $send_message = new SendMessage();
        $send_message->login($authorization,$user->id,$user->name,$title,$subheading,$link,$data,$recives);

//        $send_message->sendMessage($title,$subheading,$link,$data,$recives);
    }

}
