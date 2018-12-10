<?php

namespace App\Repositories;

use App\Models\Message;
use App\Models\MessageData;
use Illuminate\Support\Facades\DB;

class MessageRepository
{
    public function addMessage($title,$module,$data){
        $message = new Message();
        $message->title = $title;
        $message->module = $module;
        $message->save();
        $message_date = new MessageData();
        $data['message_id'] =   $message->id;
        $message_date->addAll($data);
    }

}
